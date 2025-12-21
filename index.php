<?php
/**
 * Index - Redirect to Login or Dashboard
 */
session_start();

if (isset($_SESSION['user_id'])) {
  header('Location: dashboard.php');
} else {
  header('Location: login.php');
}
exit();
