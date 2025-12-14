<?php
// This file redirects to create-assignment.php with the ID parameter
// The create-assignment.php handles both create and edit functionality
header('Location: create-assignment.php?id=' . ($_GET['id'] ?? ''));
exit();
