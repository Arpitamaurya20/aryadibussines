<?php

class Ppmbilling extends Core
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->setTimeZone();
    }

    /**
     * Get PPM billing data for a given period and optional filters
     * Filters:
     *  - start_date (Y-m-d)
     *  - end_date (Y-m-d)
     *  - CorporateID (-1 for all)
     *  - BranchID (-1 for all)
     *  - BillingStatus ('' | 'Pending' | 'Billed')
     */
    public function getBillingData($filters)
    {
        $start_date    = isset($filters['start_date']) ? $filters['start_date'] : date('Y-m-01');
        $end_date      = isset($filters['end_date']) ? $filters['end_date'] : date('Y-m-t');
        $CorporateID   = isset($filters['CorporateID']) ? (int)$filters['CorporateID'] : -1;
        $BranchID      = isset($filters['BranchID']) ? (int)$filters['BranchID'] : -1;
        $BillingStatus = isset($filters['BillingStatus']) ? $filters['BillingStatus'] : '';

        $where = " WHERE t.Status = 'Closed' AND t.PPMDate >= '$start_date' AND t.PPMDate <= '$end_date' AND t.IsActive = 1";

        if ($CorporateID != -1) {
            $where .= " AND t.CorporateID = $CorporateID";
        }
        if ($BranchID != -1) {
            $where .= " AND t.BranchID = $BranchID";
        }

        $having = "";
        if ($BillingStatus != "") {
            // pb.Status will be NULL when no billing row exists – treat that as Pending
            if ($BillingStatus == "Pending") {
                $having = " HAVING BillingStatus = 'Pending'";
            } elseif ($BillingStatus == "Billed") {
                $having = " HAVING BillingStatus = 'Billed'";
            }
        }

        /**
         * Logic:
         *  - Find all closed ppm_tickets in the period
         *  - Join temp_ppm_dates to get TempAssetInfoID for the ticket
         *  - For that TempAssetInfoID count all active PPM dates (total visits in contract)
         *  - Per visit amount = branch_assets.Amount / total_visits (fallback: Amount if no schedule found)
         *  - Join ppm_ticket_billing to get billing status & custom BilledAmount if already stored
         */

        $sql = "
            SELECT
                t.ID                         AS TicketPK,
                t.TicketID                   AS TicketNumber,
                t.PPMDate,
                t.CorporateID,
                c.CompanyName,
                t.BranchID,
                b.BranchSite,
                b.BranchCity,
                t.BranchAssetID,
                ba.EquipmentName,
                ba.Make,
                ba.Model,
                ba.EquipmentLocation,
                ba.Amount                    AS AssetContractAmount,
                tbi.Interval                 AS IntervalType,
                COUNT(DISTINCT tp_all.ID)    AS TotalScheduledVisits,
                CASE 
                    WHEN COUNT(DISTINCT tp_all.ID) > 0 
                        THEN ROUND(ba.Amount / COUNT(DISTINCT tp_all.ID), 2)
                    ELSE ba.Amount
                END                          AS CalculatedPerVisitAmount,
                pb.BilledAmount,
                pb.Status                    AS RawBillingStatus,
                pb.InvoiceNumber,
                pb.BillingPeriodStart,
                pb.BillingPeriodEnd,
                CASE 
                    WHEN pb.Status IS NULL THEN 'Pending'
                    ELSE pb.Status
                END                          AS BillingStatus
            FROM ppm_tickets t
            INNER JOIN branch b ON t.BranchID = b.ID
            INNER JOIN company c ON t.CorporateID = c.ID
            INNER JOIN branch_assets ba ON t.BranchAssetID = ba.ID
            LEFT JOIN temp_ppm_dates tp ON tp.TicketID = t.ID AND tp.IsActive = 1
            LEFT JOIN temp_branch_assets_info tbi ON tp.TempAssetInfoID = tbi.ID AND tbi.IsActive = 1
            LEFT JOIN temp_ppm_dates tp_all 
                   ON tp_all.TempAssetInfoID = tbi.ID AND tp_all.IsActive = 1
            LEFT JOIN ppm_ticket_billing pb ON pb.TicketID = t.ID
            $where
            GROUP BY t.ID
            $having
            ORDER BY c.CompanyName, b.BranchSite, t.PPMDate, t.TicketID
        ";

        $result = mysqli_query($this->conn, $sql);
        $response = array();

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Decide final billed amount to show
                $row['DisplayBilledAmount'] = ($row['BilledAmount'] !== null && $row['BilledAmount'] !== '')
                    ? (float)$row['BilledAmount']
                    : (float)$row['CalculatedPerVisitAmount'];

                $response[] = $row;
            }
        }

        return $response;
    }

    /**
     * Create or update billing row for a ticket
     */
    public function saveBillingStatus($data)
    {
        $TicketID   = (int)$data['TicketID'];
        $Status     = isset($data['Status']) ? $data['Status'] : 'Pending';
        $BilledAmt  = isset($data['BilledAmount']) && $data['BilledAmount'] !== '' ? (float)$data['BilledAmount'] : null;
        $InvoiceNo  = isset($data['InvoiceNumber']) ? $data['InvoiceNumber'] : null;
        $Notes      = isset($data['Notes']) ? mysqli_real_escape_string($this->conn, $data['Notes']) : null;
        $CreatedBy  = isset($data['CreatedBy']) ? $data['CreatedBy'] : 'System';
        $PeriodFrom = isset($data['BillingPeriodStart']) ? $data['BillingPeriodStart'] : date('Y-m-01');
        $PeriodTo   = isset($data['BillingPeriodEnd']) ? $data['BillingPeriodEnd'] : date('Y-m-t');

        // Get ticket basic info and calculate default per-visit amount
        $ticket_where = " where ID = $TicketID";
        $ticket = $this->_getTableDetails($this->conn, 'ppm_tickets', $ticket_where);
        if ($ticket == null) {
            return array(
                'error'   => true,
                'message' => 'Invalid TicketID'
            );
        }

        $BranchAssetID = $ticket['BranchAssetID'];
        $CorporateID   = $ticket['CorporateID'];
        $BranchID      = $ticket['BranchID'];

        // Get asset contract amount
        $asset = $this->_getTableDetails($this->conn, 'branch_assets', ' where ID = '.$BranchAssetID);
        $AssetContractAmount = $asset ? (float)$asset['Amount'] : 0.0;

        // Get scheduled visits count for this asset from temp tables
        $sql_visits = "
            SELECT COUNT(DISTINCT tp_all.ID) AS TotalVisits, tbi.Interval
            FROM ppm_tickets t
            LEFT JOIN temp_ppm_dates tp ON tp.TicketID = t.ID AND tp.IsActive = 1
            LEFT JOIN temp_branch_assets_info tbi ON tp.TempAssetInfoID = tbi.ID AND tbi.IsActive = 1
            LEFT JOIN temp_ppm_dates tp_all 
                   ON tp_all.TempAssetInfoID = tbi.ID AND tp_all.IsActive = 1
            WHERE t.ID = $TicketID
            GROUP BY t.ID
        ";
        $result_visits = mysqli_query($this->conn, $sql_visits);
        $TotalVisits = 0;
        $IntervalType = null;
        if ($result_visits && $row_visits = $result_visits->fetch_assoc()) {
            $TotalVisits  = (int)$row_visits['TotalVisits'];
            $IntervalType = $row_visits['Interval'];
        }

        if ($TotalVisits > 0) {
            $PerVisitAmount = round($AssetContractAmount / $TotalVisits, 2);
        } else {
            // Fallback – treat full contract amount as single visit
            $PerVisitAmount = $AssetContractAmount;
        }

        if ($BilledAmt === null) {
            $BilledAmt = $PerVisitAmount;
        }

        $CreatedDate = date('Y-m-d');
        $CreatedTime = date('H:i:s');

        // Check if billing already exists
        $existing = $this->_getTableDetails($this->conn, 'ppm_ticket_billing', ' where TicketID = '.$TicketID);
        if ($existing != null) {
            $update_sql = "
                Status = '$Status',
                BilledAmount = $BilledAmt,
                InvoiceNumber = ".($InvoiceNo ? "'$InvoiceNo'" : "NULL").",
                Notes = ".($Notes ? "'$Notes'" : "NULL").",
                BillingPeriodStart = '$PeriodFrom',
                BillingPeriodEnd = '$PeriodTo'
                where TicketID = $TicketID
            ";
            $response = $this->_UpdateTableRecords($this->conn, 'ppm_ticket_billing', $update_sql);
        } else {
            $insert_sql = "
                INSERT INTO ppm_ticket_billing
                (TicketID, CorporateID, BranchID, BranchAssetID, BillingPeriodStart, BillingPeriodEnd, `Interval`,
                 AssetContractAmount, PerVisitAmount, BilledAmount, Status, InvoiceNumber, Notes,
                 CreatedBy, CreatedDate, CreatedTime)
                VALUES
                ($TicketID, $CorporateID, $BranchID, $BranchAssetID, '$PeriodFrom', '$PeriodTo', ".($IntervalType ? "'$IntervalType'" : "NULL").",
                 $AssetContractAmount, $PerVisitAmount, $BilledAmt, '$Status', ".($InvoiceNo ? "'$InvoiceNo'" : "NULL").",
                 ".($Notes ? "'$Notes'" : "NULL").",
                 '$CreatedBy', '$CreatedDate', '$CreatedTime')
            ";
            $response = $this->_InsertTableRecords($this->conn, $insert_sql);
        }

        if (isset($response['error']) && $response['error'] == false) {
            $response['message'] = "PPM billing details saved";
        }

        return $response;
    }
}


