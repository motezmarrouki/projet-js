<?php  

include 'components/connect.php';

if(isset($_COOKIE['user_id'])){
   $user_id = $_COOKIE['user_id'];
}else{
   $user_id = '';
   header('location:login.php');
   exit;
}

if(!isset($user_type)){
   // you need to define $user_type based on your application's logic
   $user_type = '';
}

// Process reservation submission
if(isset($_POST['submit'])){
   $property_id = $_POST['property_id'];
   $property_id = filter_var($property_id, FILTER_SANITIZE_STRING);
   $date_range = $_POST['date'];
   $date_range = filter_var($date_range, FILTER_SANITIZE_STRING);
   $dates = explode(' to ', $date_range);
   $start_date = isset($dates[0]) ? $dates[0] : '';
   $end_date = isset($dates[1]) ? $dates[1] : $start_date;
   $time = $_POST['time'];
   $time = filter_var($time, FILTER_SANITIZE_STRING);
   $message = $_POST['message'];
   $message = filter_var($message, FILTER_SANITIZE_STRING);
   $status = 'pending'; // Default status for new reservations

   // Check if property exists
   $verify_property = $conn->prepare("SELECT * FROM `property` WHERE id = ?");
   $verify_property->execute([$property_id]);

   if($verify_property->rowCount() > 0){
      $fetch_property = $verify_property->fetch(PDO::FETCH_ASSOC);
      $owner_id = $fetch_property['user_id'];
      
      // Check if already reserved for this date range and time
      $check_reservation = $conn->prepare("SELECT * FROM `reservations` WHERE property_id = ? AND ((date BETWEEN ? AND ?) OR (date = ? AND time = ?)) AND status != 'cancelled'");
      $check_reservation->execute([$property_id, $start_date, $end_date, $start_date, $time]);
      
      if($check_reservation->rowCount() > 0){
         $warning_msg[] = 'Property already reserved for this date and time! Please select another time.';
      }else{
         // Insert a reservation for each day in the range
         $begin = new DateTime($start_date);
         $end = new DateTime($end_date);
         $end->modify('+1 day'); // include end date
         $interval = new DateInterval('P1D');
         $daterange = new DatePeriod($begin, $interval ,$end);

         $insert_reservation = $conn->prepare("INSERT INTO `reservations`(property_id, user_id, owner_id, date, time, message, status) VALUES(?,?,?,?,?,?,?)");

         foreach($daterange as $date){
            $d = $date->format("Y-m-d");
            // Check for existing reservation on each day
            $check_each = $conn->prepare("SELECT * FROM `reservations` WHERE property_id = ? AND date = ? AND time = ? AND status != 'cancelled'");
            $check_each->execute([$property_id, $d, $time]);
            if($check_each->rowCount() > 0){
               $warning_msg[] = "Property already reserved on $d at $time.";
               continue;
            }
            $insert_reservation->execute([$property_id, $user_id, $owner_id, $d, $time, $message, $status]);
         }
         if(empty($warning_msg)){
            $success_msg[] = 'Reservation request sent for selected dates! You will be notified once it is confirmed.';
         }
      }
   }else{
      $warning_msg[] = 'Property not found!';
   }
}

// Cancel reservation
if(isset($_POST['cancel'])){
   $reservation_id = $_POST['reservation_id'];
   $reservation_id = filter_var($reservation_id, FILTER_SANITIZE_STRING);
   
   $verify_reservation = $conn->prepare("SELECT * FROM `reservations` WHERE id = ? AND user_id = ?");
   $verify_reservation->execute([$reservation_id, $user_id]);
   
   if($verify_reservation->rowCount() > 0){
      $update_status = $conn->prepare("UPDATE `reservations` SET status = 'cancelled' WHERE id = ?");
      $update_status->execute([$reservation_id]);
      $success_msg[] = 'Reservation cancelled successfully!';
   }else{
      $warning_msg[] = 'Invalid reservation!';
   }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Property Reservations</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">

   <!-- Flatpickr CSS -->
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">

   <style>
      .reservation-form {
         background-color: var(--light-bg);
         border-radius: 0.5rem;
         padding: 2rem;
         margin-bottom: 2rem;
      }
      
      .reservation-form .box {
         width: 100%;
         border: var(--border);
         padding: 1.4rem;
         color: var(--black);
         margin: 1rem 0;
         border-radius: 0.5rem;
      }
      
      .reservation-container {
         display: grid;
         grid-template-columns: repeat(auto-fill, minmax(330px, 1fr));
         gap: 1.5rem;
         align-items: flex-start;
         justify-content: center;
         margin: 0 auto;
         max-width: 1200px;
      }
      
      .reservation-box {
         background-color: var(--white);
         box-shadow: var(--box-shadow);
         border-radius: 0.5rem;
         overflow: hidden;
         padding: 2rem;
      }
      
      .reservation-box .status {
         padding: 0.5rem 1rem;
         border-radius: 0.5rem;
         color: var(--white);
         display: inline-block;
         margin-bottom: 1rem;
         font-weight: bold;
      }
      
      .status.pending {
         background-color: #f39c12;
      }
      
      .status.confirmed {
         background-color: #27ae60;
      }
      
      .status.cancelled {
         background-color: #e74c3c;
      }
      
      .status.completed {
         background-color: #3498db;
      }
      
      .filter-container {
         display: flex;
         flex-wrap: wrap;
         gap: 1rem;
         margin-bottom: 2rem;
         background: var(--light-bg);
         padding: 1.5rem;
         border-radius: 0.5rem;
      }
      
      .filter-container select, .filter-container input {
         padding: 0.8rem;
         border-radius: 0.5rem;
         border: 1px solid var(--light-bg);
         width: 100%;
         max-width: 200px;
      }
      
      .tabs {
         display: flex;
         margin-bottom: 2rem;
         gap: 1rem;
      }
      
      .tab {
         padding: 1rem 2rem;
         background-color: var(--light-bg);
         border-radius: 0.5rem;
         cursor: pointer;
         text-align: center;
         transition: all 0.3s ease;
      }
      
      .tab.active {
         background-color: var(--main-color);
         color: var(--white);
         font-weight: bold;
      }
      
      .tab-content {
         display: none;
      }
      
      .tab-content.active {
         display: block;
      }
      
      .property-image {
         width: 100%;
         height: 200px;
         object-fit: cover;
         border-radius: 0.5rem;
         margin-bottom: 1rem;
      }
   </style>

</head>
<body>
   
<?php include 'components/user_header.php'; ?>

<section class="reservations">

   <h1 class="heading">property reservations</h1>

   <div class="tabs">
      <div class="tab active" data-tab="my-reservations">My Reservations</div>
      <div class="tab" data-tab="make-reservation">Make a Reservation</div>
      <?php if($user_type == 'owner'): ?>
      <div class="tab" data-tab="owner-reservations">Manage Property Reservations</div>
      <?php endif; ?>
   </div>

   <!-- My Reservations Tab -->
   <div class="tab-content active" id="my-reservations">
      <div class="filter-container">
         <form action="" method="GET" id="filter-form" class="search-filter">
            <input type="hidden" name="tab" value="my-reservations">
            <select name="status" class="input" onchange="document.getElementById('filter-form').submit()">
               <option value="" <?= (!isset($_GET['status']) || $_GET['status'] == '') ? 'selected' : ''; ?>>all status</option>
               <option value="pending" <?= (isset($_GET['status']) && $_GET['status'] == 'pending') ? 'selected' : ''; ?>>pending</option>
               <option value="confirmed" <?= (isset($_GET['status']) && $_GET['status'] == 'confirmed') ? 'selected' : ''; ?>>confirmed</option>
               <option value="cancelled" <?= (isset($_GET['status']) && $_GET['status'] == 'cancelled') ? 'selected' : ''; ?>>cancelled</option>
               <option value="completed" <?= (isset($_GET['status']) && $_GET['status'] == 'completed') ? 'selected' : ''; ?>>completed</option>
            </select>
         </form>
      </div>

      <div class="reservation-container">
         <?php
            $status_filter = '';
            $params = [$user_id];
            
            if(isset($_GET['status']) && !empty($_GET['status'])){
               $status_filter = "AND status = ?";
               $params[] = $_GET['status'];
            }
            
            $select_reservations = $conn->prepare("SELECT * FROM `reservations` WHERE user_id = ? $status_filter ORDER BY date DESC");
            $stmt = $pdo->prepare("SELECT * FROM reservations ORDER BY reservation_date DESC");


            
            if($select_reservations->rowCount() > 0){
               while($fetch_reservation = $select_reservations->fetch(PDO::FETCH_ASSOC)){
                  
                  // Get property details
                  $select_property = $conn->prepare("SELECT * FROM `property` WHERE id = ?");
                  $select_property->execute([$fetch_reservation['property_id']]);
                  $fetch_property = $select_property->fetch(PDO::FETCH_ASSOC);
                  
                  // Get owner details
                  $select_owner = $conn->prepare("SELECT * FROM `users` WHERE id = ?");
                  $select_owner->execute([$fetch_reservation['owner_id']]);
                  $fetch_owner = $select_owner->fetch(PDO::FETCH_ASSOC);
         ?>
         <div class="reservation-box">
            <span class="status <?= $fetch_reservation['status']; ?>"><?= $fetch_reservation['status']; ?></span>
            
            <?php if(!empty($fetch_property['image_01'])): ?>
            <img src="uploaded_files/<?= $fetch_property['image_01']; ?>" class="property-image" alt="">
            <?php endif; ?>
            
            <h3><?= $fetch_property['property_name']; ?></h3>
            <p><i class="fas fa-map-marker-alt"></i> <span><?= $fetch_property['address']; ?></span></p>
            <p><i class="fas fa-calendar"></i> <span><?= date('d M Y', strtotime($fetch_reservation['date'])); ?></span></p>
            <p><i class="fas fa-clock"></i> <span><?= $fetch_reservation['time']; ?></span></p>
            <p><i class="fas fa-user"></i> <span>Owner: <?= $fetch_owner['name']; ?></span></p>
            
            <?php if($fetch_reservation['message'] != ''): ?>
            <p><i class="fas fa-comment"></i> <span>Message: <?= $fetch_reservation['message']; ?></span></p>
            <?php endif; ?>
            
            <div class="flex-btn">
               <a href="view_property.php?get_id=<?= $fetch_property['id']; ?>" class="btn">view property</a>
               
               <?php if($fetch_reservation['status'] == 'pending' || $fetch_reservation['status'] == 'confirmed'): ?>
               <form action="" method="POST">
                  <input type="hidden" name="reservation_id" value="<?= $fetch_reservation['id']; ?>">
                  <input type="submit" value="cancel reservation" name="cancel" class="btn" onclick="return confirm('Cancel this reservation?');">
               </form>
               <?php endif; ?>
            </div>
         </div>
         <?php
               }
            }else{
               echo '<p class="empty">no reservations found!</p>';
            }
         ?>
      </div>
   </div>

   <!-- Make Reservation Tab -->
   <div class="tab-content" id="make-reservation">
      <div class="reservation-form">
         <form action="" method="POST">
            <h3>schedule a property viewing</h3>
            
            <select name="property_id" class="box" required>
               <option value="" disabled selected>select property</option>
               <?php
                  $select_properties = $conn->prepare("SELECT * FROM `property` WHERE status != 'sold' ORDER BY property_name");
                  $select_properties->execute();
                  
                  if($select_properties->rowCount() > 0){
                     while($fetch_property = $select_properties->fetch(PDO::FETCH_ASSOC)){
                        echo '<option value="'.$fetch_property['id'].'">'.$fetch_property['property_name'].' - '.$fetch_property['address'].'</option>';
                     }
                  }
               ?>
            </select>
            
            <input type="text" id="date-range" name="date" class="box" placeholder="Select date range" required autocomplete="off">
            
            <select name="time" class="box" required>
               <option value="" disabled selected>select time</option>
               <option value="09:00 AM">09:00 AM</option>
               <option value="10:00 AM">10:00 AM</option>
               <option value="11:00 AM">11:00 AM</option>
               <option value="12:00 PM">12:00 PM</option>
               <option value="01:00 PM">01:00 PM</option>
               <option value="02:00 PM">02:00 PM</option>
               <option value="03:00 PM">03:00 PM</option>
               <option value="04:00 PM">04:00 PM</option>
               <option value="05:00 PM">05:00 PM</option>
            </select>
            
            <textarea name="message" class="box" placeholder="additional message or special requests" cols="30" rows="10"></textarea>
            
            <input type="submit" value="schedule viewing" name="submit" class="btn">
         </form>
      </div>
   </div>

   <!-- Owner Reservations Tab (visible only to property owners) -->
   <?php if($user_type == 'owner'): ?>
   <div class="tab-content" id="owner-reservations">
      <div class="filter-container">
         <form action="" method="GET" id="owner-filter-form" class="search-filter">
            <input type="hidden" name="tab" value="owner-reservations">
            <select name="o_status" class="input" onchange="document.getElementById('owner-filter-form').submit()">
               <option value="" <?= (!isset($_GET['o_status']) || $_GET['o_status'] == '') ? 'selected' : ''; ?>>all status</option>
               <option value="pending" <?= (isset($_GET['o_status']) && $_GET['o_status'] == 'pending') ? 'selected' : ''; ?>>pending</option>
               <option value="confirmed" <?= (isset($_GET['o_status']) && $_GET['o_status'] == 'confirmed') ? 'selected' : ''; ?>>confirmed</option>
               <option value="cancelled" <?= (isset($_GET['o_status']) && $_GET['o_status'] == 'cancelled') ? 'selected' : ''; ?>>cancelled</option>
               <option value="completed" <?= (isset($_GET['o_status']) && $_GET['o_status'] == 'completed') ? 'selected' : ''; ?>>completed</option>
            </select>
         </form>
      </div>

      <div class="reservation-container">
         <?php
            $status_filter = '';
            $params = [$user_id];
            
            if(isset($_GET['o_status']) && !empty($_GET['o_status'])){
               $status_filter = "AND status = ?";
               $params[] = $_GET['o_status'];
            }
            
            $select_owner_reservations = $conn->prepare("SELECT * FROM `reservations` WHERE owner_id = ? $status_filter ORDER BY date DESC");
            $select_owner_reservations->execute($params);
            
            if($select_owner_reservations->rowCount() > 0){
               while($fetch_reservation = $select_owner_reservations->fetch(PDO::FETCH_ASSOC)){
                  
                  // Get property details
                  $select_property = $conn->prepare("SELECT * FROM `property` WHERE id = ?");
                  $select_property->execute([$fetch_reservation['property_id']]);
                  $fetch_property = $select_property->fetch(PDO::FETCH_ASSOC);
                  
                  // Get user details
                  $select_user = $conn->prepare("SELECT * FROM `users` WHERE id = ?");
                  $select_user->execute([$fetch_reservation['user_id']]);
                  $fetch_user = $select_user->fetch(PDO::FETCH_ASSOC);
         ?>
         <div class="reservation-box">
            <span class="status <?= $fetch_reservation['status']; ?>"><?= $fetch_reservation['status']; ?></span>
            
            <?php if(!empty($fetch_property['image_01'])): ?>
            <img src="uploaded_files/<?= $fetch_property['image_01']; ?>" class="property-image" alt="">
            <?php endif; ?>
            
            <h3><?= $fetch_property['property_name']; ?></h3>
            <p><i class="fas fa-map-marker-alt"></i> <span><?= $fetch_property['address']; ?></span></p>
            <p><i class="fas fa-calendar"></i> <span><?= date('d M Y', strtotime($fetch_reservation['date'])); ?></span></p>
            <p><i class="fas fa-clock"></i> <span><?= $fetch_reservation['time']; ?></span></p>
            <p><i class="fas fa-user"></i> <span>Requested by: <?= $fetch_user['name']; ?></span></p>
            <p><i class="fas fa-phone"></i> <span>Contact: <?= $fetch_user['number']; ?></span></p>
            
            <?php if($fetch_reservation['message'] != ''): ?>
            <p><i class="fas fa-comment"></i> <span>Message: <?= $fetch_reservation['message']; ?></span></p>
            <?php endif; ?>
            
            <div class="flex-btn">
               <?php if($fetch_reservation['status'] == 'pending'): ?>
               <form action="" method="POST">
                  <input type="hidden" name="reservation_id" value="<?= $fetch_reservation['id']; ?>">
                  <input type="submit" value="confirm" name="confirm" class="btn">
                  <input type="submit" value="decline" name="decline" class="btn" onclick="return confirm('Decline this reservation?');">
               </form>
               <?php elseif($fetch_reservation['status'] == 'confirmed'): ?>
               <form action="" method="POST">
                  <input type="hidden" name="reservation_id" value="<?= $fetch_reservation['id']; ?>">
                  <input type="submit" value="mark as completed" name="complete" class="btn">
               </form>
               <?php endif; ?>
            </div>
         </div>
         <?php
               }
            }else{
               echo '<p class="empty">no reservation requests found!</p>';
            }
         ?>
      </div>
   </div>
   <?php endif; ?>

</section>

<!-- Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>

<?php include 'components/footer.php'; ?>

<!-- custom js file link  -->
<script src="js/script.js"></script>

<script>
   // Initialize Flatpickr for date range
   flatpickr("#date-range", {
      mode: "range",
      dateFormat: "Y-m-d",
      minDate: "today",
      allowInput: true
   });

   // Tab functionality
   const tabs = document.querySelectorAll('.tab');
   const tabContents = document.querySelectorAll('.tab-content');
   
   // Check URL parameters for active tab
   const urlParams = new URLSearchParams(window.location.search);
   const tabParam = urlParams.get('tab');
   
   if(tabParam) {
      // Set active tab based on URL parameter
      tabs.forEach(tab => {
         if(tab.dataset.tab === tabParam) {
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            
            tabContents.forEach(content => {
               content.classList.remove('active');
               if(content.id === tabParam) {
                  content.classList.add('active');
               }
            });
         }
      });
   }
   
   tabs.forEach(tab => {
      tab.addEventListener('click', () => {
         // Remove active class from all tabs
         tabs.forEach(t => t.classList.remove('active'));
         tabContents.forEach(content => content.classList.remove('active'));
         
         // Add active class to clicked tab
         tab.classList.add('active');
         
         // Show corresponding content
         const tabId = tab.dataset.tab;
         document.getElementById(tabId).classList.add('active');
      });
   });
</script>

<?php
// Process owner actions
if(isset($_POST['confirm']) && $user_type == 'owner'){
   $reservation_id = $_POST['reservation_id'];
   $reservation_id = filter_var($reservation_id, FILTER_SANITIZE_STRING);
   
   $verify_reservation = $conn->prepare("SELECT * FROM `reservations` WHERE id = ? AND owner_id = ? AND status = 'pending'");
   $verify_reservation->execute([$reservation_id, $user_id]);
   
   if($verify_reservation->rowCount() > 0){
      $update_status = $conn->prepare("UPDATE `reservations` SET status = 'confirmed' WHERE id = ?");
      $update_status->execute([$reservation_id]);
      $success_msg[] = 'Reservation confirmed successfully!';
   }else{
      $warning_msg[] = 'Invalid reservation request!';
   }
}

if(isset($_POST['decline']) && $user_type == 'owner'){
   $reservation_id = $_POST['reservation_id'];
   $reservation_id = filter_var($reservation_id, FILTER_SANITIZE_STRING);
   
   $verify_reservation = $conn->prepare("SELECT * FROM `reservations` WHERE id = ? AND owner_id = ? AND status = 'pending'");
   $verify_reservation->execute([$reservation_id, $user_id]);
   
   if($verify_reservation->rowCount() > 0){
      $update_status = $conn->prepare("UPDATE `reservations` SET status = 'cancelled' WHERE id = ?");
      $update_status->execute([$reservation_id]);
      $success_msg[] = 'Reservation declined!';
   }else{
      $warning_msg[] = 'Invalid reservation request!';
   }
}

if(isset($_POST['complete']) && $user_type == 'owner'){
   $reservation_id = $_POST['reservation_id'];
   $reservation_id = filter_var($reservation_id, FILTER_SANITIZE_STRING);
   
   $verify_reservation = $conn->prepare("SELECT * FROM `reservations` WHERE id = ? AND owner_id = ? AND status = 'confirmed'");
   $verify_reservation->execute([$reservation_id, $user_id]);
   
   if($verify_reservation->rowCount() > 0){
      $update_status = $conn->prepare("UPDATE `reservations` SET status = 'completed' WHERE id = ?");
      $update_status->execute([$reservation_id]);
      $success_msg[] = 'Reservation marked as completed!';
   }else{
      $warning_msg[] = 'Invalid reservation!';
   }
}
?>

<?php include 'components/message.php'; ?>

</body>
</html>

