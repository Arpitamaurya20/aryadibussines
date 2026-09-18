<?php
// Connect to the DB the user actually uses
$conn = mysqli_connect("localhost", "root", "", "aryadibussiness");
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// 1. Pipeline Stages
$q_stages = "CREATE TABLE IF NOT EXISTS `crm_pipeline_stages` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `StageName` varchar(100) NOT NULL,
  `Probability` int(11) DEFAULT '0',
  `SortOrder` int(11) DEFAULT '0',
  `IsActive` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
if(mysqli_query($conn, $q_stages)) {
    echo "crm_pipeline_stages checked/created.\n";
    // Insert default stages if empty
    $res = mysqli_query($conn, "SELECT COUNT(*) as c FROM crm_pipeline_stages");
    $row = mysqli_fetch_assoc($res);
    if($row['c'] == 0) {
        mysqli_query($conn, "INSERT INTO crm_pipeline_stages (StageName, Probability, SortOrder) VALUES 
        ('Inquiry', 10, 1), ('Qualified', 30, 2), ('Need Analysis', 50, 3), 
        ('Quotation', 70, 4), ('Negotiation', 90, 5), ('Won', 100, 6), ('Lost', 0, 7)");
    }
}

// 2. Opportunities
$q_opp = "CREATE TABLE IF NOT EXISTS `crm_opportunities` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `LeadID` int(11) DEFAULT NULL,
  `AccountID` int(11) DEFAULT NULL,
  `ContactID` int(11) DEFAULT NULL,
  `OpportunityName` varchar(255) NOT NULL,
  `Amount` decimal(15,2) DEFAULT '0.00',
  `ExpectedCloseDate` date DEFAULT NULL,
  `StageID` int(11) DEFAULT NULL,
  `Probability` int(11) DEFAULT '0',
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedDate` date DEFAULT NULL,
  `CreatedTime` time DEFAULT NULL,
  `IsActive` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
if(mysqli_query($conn, $q_opp)) echo "crm_opportunities checked/created.\n";

// 3. Follow-ups
$q_followup = "CREATE TABLE IF NOT EXISTS `crm_followups` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `LeadID` int(11) DEFAULT NULL,
  `AccountID` int(11) DEFAULT NULL,
  `Type` varchar(50) NOT NULL, -- Call, Meeting, Email, etc.
  `Status` varchar(50) DEFAULT 'Pending', -- Pending, Completed, Missed
  `FollowupDate` date NOT NULL,
  `FollowupTime` time NOT NULL,
  `Notes` text,
  `AssignedTo` int(11) DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedDate` date DEFAULT NULL,
  `CreatedTime` time DEFAULT NULL,
  `IsActive` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
if(mysqli_query($conn, $q_followup)) echo "crm_followups checked/created.\n";

// 4. Contacts
$q_contacts = "CREATE TABLE IF NOT EXISTS `crm_contacts` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `AccountID` int(11) DEFAULT NULL,
  `LeadID` int(11) DEFAULT NULL,
  `FirstName` varchar(100) NOT NULL,
  `LastName` varchar(100) DEFAULT NULL,
  `Email` varchar(150) DEFAULT NULL,
  `Phone` varchar(20) DEFAULT NULL,
  `Role` varchar(100) DEFAULT NULL, -- Decision Maker, Accounts, etc.
  `IsPrimary` tinyint(1) DEFAULT '0',
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedDate` date DEFAULT NULL,
  `CreatedTime` time DEFAULT NULL,
  `IsActive` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
if(mysqli_query($conn, $q_contacts)) echo "crm_contacts checked/created.\n";

echo "Phase 2 Database update complete!\n";
?>
