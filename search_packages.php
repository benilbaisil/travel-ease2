<?php

// Update any search queries to include the active check
$search_term = '%' . $_GET['search'] . '%';
$sql = "SELECT * FROM travel_packages 
        WHERE active = 1 
        AND (package_name LIKE ? OR destination LIKE ? OR description LIKE ?)
        ORDER BY id DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $search_term, $search_term, $search_term); 