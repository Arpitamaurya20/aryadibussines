<?php

class Workshop extends Core
{
    private $conn;
    
    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->setTimeZone();
    }

    // Generate slug from title
    public function generateSlug($title)
    {
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        // Check if slug exists, append number if needed
        $originalSlug = $slug;
        $counter = 1;
        while ($this->checkSlugExists($slug)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    // Check if slug exists
    public function checkSlugExists($slug, $excludeId = null)
    {
        $where = "WHERE slug = '" . $this->conn->real_escape_string($slug) . "'";
        if ($excludeId !== null) {
            $where .= " AND ID != " . (int)$excludeId;
        }
        $result = $this->_getTableRecords($this->conn, 'workshops', $where);
        return count($result) > 0;
    }

    // Check if category slug exists
    public function checkCategorySlugExists($slug, $excludeId = null)
    {
        $where = "WHERE category_slug = '" . $this->conn->real_escape_string($slug) . "'";
        if ($excludeId !== null) {
            $where .= " AND ID != " . (int)$excludeId;
        }
        $result = $this->_getTableRecords($this->conn, 'workshop_categories', $where);
        return count($result) > 0;
    }

    // Generate category slug
    public function generateCategorySlug($categoryName, $excludeId = null)
    {
        $slug = strtolower(trim($categoryName));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        $originalSlug = $slug;
        $counter = 1;
        while ($this->checkCategorySlugExists($slug, $excludeId)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    // Calculate duration in minutes
    public function calculateDuration($startTime, $endTime)
    {
        if (empty($startTime) || empty($endTime)) {
            return null;
        }
        
        $start = strtotime($startTime);
        $end = strtotime($endTime);
        
        if ($end < $start) {
            // Handle next day scenario
            $end += 86400; // Add 24 hours
        }
        
        return round(($end - $start) / 60); // Return minutes
    }

    // Insert Workshop
    public function insertWorkshop($data)
    {
        // Generate slug if not provided
        if (empty($data['slug']) && !empty($data['workshop_title'])) {
            $data['slug'] = $this->generateSlug($data['workshop_title']);
        }
        
        // Calculate duration if start and end time are provided
        if (!empty($data['start_time']) && !empty($data['end_time']) && empty($data['duration_minutes'])) {
            $data['duration_minutes'] = $this->calculateDuration($data['start_time'], $data['end_time']);
        }
        
        // Generate certificate verification code if certificate is enabled
        if (!empty($data['provide_certificate']) && $data['provide_certificate'] == 1 && empty($data['certificate_verification_code'])) {
            $data['certificate_verification_code'] = 'WS-' . strtoupper(uniqid());
        }
        
        return $this->_InsertTableRecords_prepare($this->conn, 'workshops', $data);
    }

    // Update Workshop
    public function updateWorkshop($data, $id)
    {
        // Generate slug if title changed and slug not provided
        if (!empty($data['workshop_title']) && empty($data['slug'])) {
            $existing = $this->getWorkshopById($id);
            if ($existing && $existing['workshop_title'] != $data['workshop_title']) {
                $data['slug'] = $this->generateSlug($data['workshop_title']);
            }
        }
        
        // Calculate duration if start and end time are provided
        if (!empty($data['start_time']) && !empty($data['end_time'])) {
            $data['duration_minutes'] = $this->calculateDuration($data['start_time'], $data['end_time']);
        }
        
        $where = "ID = " . (int)$id;
        return $this->_UpdateTableRecords_prepare($this->conn, 'workshops', $data, $where);
    }

    // Delete Workshop
    public function deleteWorkshop($id)
    {
        $where = "ID = " . (int)$id;
        $data = array('IsActive' => 0);
        return $this->_UpdateTableRecords_prepare($this->conn, 'workshops', $data, $where);
    }

    // Get Workshop by ID
    public function getWorkshopById($id)
    {
        $where = "WHERE ID = " . (int)$id . " AND IsActive = 1";
        return $this->_getTableDetails($this->conn, 'workshops', $where);
    }

    // Get Workshop by Slug
    public function getWorkshopBySlug($slug)
    {
        $where = "WHERE slug = '" . $this->conn->real_escape_string($slug) . "' AND IsActive = 1 AND status = 'published'";
        return $this->_getTableDetails($this->conn, 'workshops', $where);
    }

    // Get All Workshops
    public function getAllWorkshops($limit = null, $offset = null, $categoryId = null, $status = null)
    {
        $where = "WHERE IsActive = 1";
        
        if ($categoryId !== null) {
            $where .= " AND category_id = " . (int)$categoryId;
        }
        
        if ($status !== null) {
            $where .= " AND status = '" . $this->conn->real_escape_string($status) . "'";
        }
        
        $where .= " ORDER BY CreatedDate DESC, ID DESC";
        
        if ($limit !== null) {
            $where .= " LIMIT " . (int)$limit;
            if ($offset !== null) {
                $where .= " OFFSET " . (int)$offset;
            }
        }
        
        return $this->_getTableRecords($this->conn, 'workshops', $where);
    }

    // Get Published Workshops
    public function getPublishedWorkshops($limit = null, $offset = null, $categoryId = null)
    {
        return $this->getAllWorkshops($limit, $offset, $categoryId, 'published');
    }

    // Get Featured Workshops
    public function getFeaturedWorkshops($limit = 5)
    {
        $where = "WHERE IsActive = 1 AND status = 'published' AND featured = 1 ORDER BY CreatedDate DESC LIMIT " . (int)$limit;
        return $this->_getTableRecords($this->conn, 'workshops', $where);
    }

    // Get Upcoming Workshops
    public function getUpcomingWorkshops($limit = 10)
    {
        $today = date('Y-m-d');
        $where = "WHERE IsActive = 1 AND status = 'published' AND (session_date >= '$today' OR session_date IS NULL) ORDER BY session_date ASC, start_time ASC LIMIT " . (int)$limit;
        return $this->_getTableRecords($this->conn, 'workshops', $where);
    }

    // Search Workshops
    public function searchWorkshops($searchTerm, $limit = 50)
    {
        $searchTerm = $this->conn->real_escape_string($searchTerm);
        $where = "WHERE IsActive = 1 AND status = 'published' AND (
            workshop_title LIKE '%$searchTerm%' OR 
            short_description LIKE '%$searchTerm%' OR 
            detailed_description LIKE '%$searchTerm%' OR
            tags LIKE '%$searchTerm%'
        ) ORDER BY CreatedDate DESC LIMIT " . (int)$limit;
        
        return $this->_getTableRecords($this->conn, 'workshops', $where);
    }

    // Increment View Count
    public function incrementViewCount($id)
    {
        $sql = "UPDATE workshops SET views_count = views_count + 1 WHERE ID = " . (int)$id;
        $this->conn->query($sql);
    }

    // Get Available Seats
    public function getAvailableSeats($workshopId)
    {
        $workshop = $this->getWorkshopById($workshopId);
        if (!$workshop || empty($workshop['total_seats'])) {
            return null;
        }
        
        $where = "WHERE workshop_id = " . (int)$workshopId . " AND IsActive = 1 AND payment_status IN ('paid', 'pending') AND is_waiting_list = 0";
        $registered = $this->_getTotalRows($this->conn, 'workshop_registrations', $where);
        
        return max(0, $workshop['total_seats'] - $registered);
    }

    // Check if Registration is Open
    public function isRegistrationOpen($workshopId)
    {
        $workshop = $this->getWorkshopById($workshopId);
        if (!$workshop || $workshop['status'] != 'published') {
            return false;
        }
        
        // Check registration deadline
        if (!empty($workshop['registration_deadline'])) {
            $deadline = strtotime($workshop['registration_deadline']);
            if (time() > $deadline) {
                return false;
            }
        }
        
        // Check if auto-close is enabled and seats are full
        if (!empty($workshop['auto_close_registration']) && $workshop['auto_close_registration'] == 1) {
            $availableSeats = $this->getAvailableSeats($workshopId);
            if ($availableSeats !== null && $availableSeats <= 0) {
                // Check if waiting list is allowed
                if (empty($workshop['allow_waiting_list']) || $workshop['allow_waiting_list'] == 0) {
                    return false;
                }
            }
        }
        
        return true;
    }

    // Workshop Categories
    public function insertWorkshopCategory($data)
    {
        if (empty($data['category_slug']) && !empty($data['category_name'])) {
            $data['category_slug'] = $this->generateCategorySlug($data['category_name']);
        }
        return $this->_InsertTableRecords_prepare($this->conn, 'workshop_categories', $data);
    }

    public function updateWorkshopCategory($data, $id)
    {
        if (!empty($data['category_name']) && empty($data['category_slug'])) {
            $existing = $this->getWorkshopCategoryById($id);
            if ($existing && $existing['category_name'] != $data['category_name']) {
                $data['category_slug'] = $this->generateCategorySlug($data['category_name'], $id);
            }
        }
        $where = "ID = " . (int)$id;
        return $this->_UpdateTableRecords_prepare($this->conn, 'workshop_categories', $data, $where);
    }

    public function deleteWorkshopCategory($id)
    {
        $where = "ID = " . (int)$id;
        $data = array('IsActive' => 0);
        return $this->_UpdateTableRecords_prepare($this->conn, 'workshop_categories', $data, $where);
    }

    public function getWorkshopCategoryById($id)
    {
        $where = "WHERE ID = " . (int)$id . " AND IsActive = 1";
        return $this->_getTableDetails($this->conn, 'workshop_categories', $where);
    }

    public function getAllWorkshopCategories()
    {
        $where = "WHERE IsActive = 1 ORDER BY category_name ASC";
        return $this->_getTableRecords($this->conn, 'workshop_categories', $where);
    }

    // Workshop Speakers
    public function insertWorkshopSpeaker($data)
    {
        return $this->_InsertTableRecords_prepare($this->conn, 'workshop_speakers', $data);
    }

    public function updateWorkshopSpeaker($data, $id)
    {
        $where = "ID = " . (int)$id;
        return $this->_UpdateTableRecords_prepare($this->conn, 'workshop_speakers', $data, $where);
    }

    public function deleteWorkshopSpeaker($id)
    {
        $where = "ID = " . (int)$id;
        $data = array('IsActive' => 0);
        return $this->_UpdateTableRecords_prepare($this->conn, 'workshop_speakers', $data, $where);
    }

    public function getWorkshopSpeakers($workshopId)
    {
        $where = "WHERE workshop_id = " . (int)$workshopId . " AND IsActive = 1 ORDER BY display_order ASC, ID ASC";
        return $this->_getTableRecords($this->conn, 'workshop_speakers', $where);
    }

    // Workshop Registrations
    public function insertWorkshopRegistration($data)
    {
        // Set registration time
        if (empty($data['registration_time'])) {
            $data['registration_time'] = date('Y-m-d H:i:s');
        }
        
        // Calculate final amount
        if (empty($data['final_amount']) && !empty($data['payment_amount'])) {
            $data['final_amount'] = $data['payment_amount'];
            if (!empty($data['discount_applied'])) {
                $data['final_amount'] = $data['payment_amount'] - $data['discount_applied'];
            }
        }
        
        // Check if waiting list
        $workshop = $this->getWorkshopById($data['workshop_id']);
        if ($workshop) {
            $availableSeats = $this->getAvailableSeats($data['workshop_id']);
            if ($availableSeats !== null && $availableSeats <= 0) {
                if (!empty($workshop['allow_waiting_list']) && $workshop['allow_waiting_list'] == 1) {
                    $data['is_waiting_list'] = 1;
                    // Get waiting list position
                    $where = "WHERE workshop_id = " . (int)$data['workshop_id'] . " AND is_waiting_list = 1";
                    $waitingCount = $this->_getTotalRows($this->conn, 'workshop_registrations', $where);
                    $data['waiting_list_position'] = $waitingCount + 1;
                }
            }
        }
        
        $result = $this->_InsertTableRecords_prepare($this->conn, 'workshop_registrations', $data);
        
        // Update workshop registration count
        if (!$result['error'] && !empty($data['is_waiting_list']) && $data['is_waiting_list'] == 0) {
            $this->updateWorkshopRegistrationCount($data['workshop_id']);
        }
        
        return $result;
    }

    public function updateWorkshopRegistration($data, $id)
    {
        $where = "ID = " . (int)$id;
        return $this->_UpdateTableRecords_prepare($this->conn, 'workshop_registrations', $data, $where);
    }

    public function getWorkshopRegistrations($workshopId, $limit = null, $offset = null)
    {
        $where = "WHERE workshop_id = " . (int)$workshopId . " AND IsActive = 1 ORDER BY registration_time DESC";
        
        if ($limit !== null) {
            $where .= " LIMIT " . (int)$limit;
            if ($offset !== null) {
                $where .= " OFFSET " . (int)$offset;
            }
        }
        
        return $this->_getTableRecords($this->conn, 'workshop_registrations', $where);
    }

    public function getRegistrationById($id)
    {
        $where = "WHERE ID = " . (int)$id . " AND IsActive = 1";
        return $this->_getTableDetails($this->conn, 'workshop_registrations', $where);
    }

    public function updateWorkshopRegistrationCount($workshopId)
    {
        $where = "WHERE workshop_id = " . (int)$workshopId . " AND IsActive = 1 AND payment_status IN ('paid', 'pending') AND is_waiting_list = 0";
        $count = $this->_getTotalRows($this->conn, 'workshop_registrations', $where);
        
        $data = array('total_registrations' => $count);
        $whereUpdate = "ID = " . (int)$workshopId;
        $this->_UpdateTableRecords_prepare($this->conn, 'workshops', $data, $whereUpdate);
    }

    // Generate Schema Markup for SEO
    public function generateSchemaMarkup($workshopData)
    {
        if (empty($workshopData)) {
            return '';
        }
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Event',
            'name' => $workshopData['workshop_title'] ?? '',
            'description' => $workshopData['short_description'] ?? '',
            'startDate' => '',
            'endDate' => '',
            'eventAttendanceMode' => '',
            'eventStatus' => 'https://schema.org/EventScheduled',
            'location' => array()
        );
        
        // Set dates
        if (!empty($workshopData['session_date']) && !empty($workshopData['start_time'])) {
            $startDateTime = $workshopData['session_date'] . 'T' . $workshopData['start_time'];
            $schema['startDate'] = $startDateTime;
        }
        
        if (!empty($workshopData['session_date']) && !empty($workshopData['end_time'])) {
            $endDateTime = $workshopData['session_date'] . 'T' . $workshopData['end_time'];
            $schema['endDate'] = $endDateTime;
        }
        
        // Set attendance mode
        $mode = $workshopData['workshop_mode'] ?? 'online';
        if ($mode == 'online') {
            $schema['eventAttendanceMode'] = 'https://schema.org/OnlineEventAttendanceMode';
            if (!empty($workshopData['meeting_link'])) {
                $schema['location'] = array(
                    '@type' => 'VirtualLocation',
                    'url' => $workshopData['meeting_link']
                );
            }
        } elseif ($mode == 'offline') {
            $schema['eventAttendanceMode'] = 'https://schema.org/OfflineEventAttendanceMode';
            if (!empty($workshopData['venue_name'])) {
                $schema['location'] = array(
                    '@type' => 'Place',
                    'name' => $workshopData['venue_name'] ?? '',
                    'address' => array(
                        '@type' => 'PostalAddress',
                        'streetAddress' => $workshopData['venue_address'] ?? '',
                        'addressLocality' => $workshopData['city'] ?? ''
                    )
                );
            }
        } else {
            $schema['eventAttendanceMode'] = 'https://schema.org/MixedEventAttendanceMode';
        }
        
        // Add organizer
        $speakers = $this->getWorkshopSpeakers($workshopData['ID'] ?? 0);
        if (!empty($speakers)) {
            $organizers = array();
            foreach ($speakers as $speaker) {
                $organizers[] = array(
                    '@type' => 'Person',
                    'name' => $speaker['speaker_name'] ?? ''
                );
            }
            if (!empty($organizers)) {
                $schema['organizer'] = count($organizers) == 1 ? $organizers[0] : $organizers;
            }
        }
        
        // Add image
        if (!empty($workshopData['thumbnail_image'])) {
            $schema['image'] = $workshopData['thumbnail_image'];
        }
        
        // Add offers (pricing)
        if (!empty($workshopData['pricing_type'])) {
            $offer = array(
                '@type' => 'Offer',
                'availability' => 'https://schema.org/InStock'
            );
            
            if ($workshopData['pricing_type'] == 'free') {
                $offer['price'] = '0';
                $offer['priceCurrency'] = 'INR';
            } else {
                $price = $workshopData['price'] ?? 0;
                $offer['price'] = (string)$price;
                $offer['priceCurrency'] = 'INR';
            }
            
            $schema['offers'] = $offer;
        }
        
        return json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
}

