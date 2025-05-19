<?php  
include 'components/connect.php';

if(isset($_COOKIE['user_id'])){
   $user_id = $_COOKIE['user_id'];
}else{
   $user_id = '';
}

include 'components/save_send.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Search Page</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
   <link rel="stylesheet" href="css/style.css">
</head>
<body>
   
<?php include 'components/user_header.php'; ?>

<!-- search filter section starts -->
<section class="filters" style="padding-bottom: 0;">
   <form action="" method="post">
      <div id="close-filter"><i class="fas fa-times"></i></div>
      <h3>filter your search</h3>
      <div class="flex">
      <div class="flex">
  <div class="box">
    <p>Location <span>*</span></p>
    <input type="text" name="location" placeholder="Enter location" class="input">
  </div>
  
  <div class="box">
    <p>Property Type <span>*</span></p>
    <select name="type" class="input">
      <option value="">Select Type</option>
      <option value="flat">Flat</option>
      <option value="house">House</option>
      <option value="villa">Villa</option>
      <option value="bungalow">Bungalow</option>
    </select>
  </div>

  <div class="box">
    <p>Offer Type <span>*</span></p>
    <select name="offer" class="input">
      <option value="">Select Offer</option>
      <option value="sale">Sale</option>
      <option value="rent">Rent</option>
    </select>
  </div>

  <div class="box">
    <p>Minimum Price <span>*</span></p>
    <input type="number" name="min" placeholder="Minimum price" class="input" min="0">
  </div>

  <div class="box">
    <p>Maximum Price <span>*</span></p>
    <input type="number" name="max" placeholder="Maximum price" class="input" min="0">
  </div>

  <div class="box">
    <p>Bedrooms (BHK) <span>*</span></p>
    <select name="bhk" class="input">
      <option value="">Select BHK</option>
      <option value="1">1 BHK</option>
      <option value="2">2 BHK</option>
      <option value="3">3 BHK</option>
      <option value="4">4 BHK</option>
      <option value="5">5 BHK</option>
    </select>
  </div>

  <div class="box">
    <p>Property Status <span>*</span></p>
    <select name="status" class="input">
      <option value="">Select Status</option>
      <option value="ready to move">Ready to Move</option>
      <option value="under construction">Under Construction</option>
    </select>
  </div>

  <div class="box">
    <p>Furnishing <span>*</span></p>
    <select name="furnished" class="input">
      <option value="">Select Furnishing</option>
      <option value="furnished">Furnished</option>
      <option value="semi-furnished">Semi-Furnished</option>
      <option value="unfurnished">Unfurnished</option>
    </select>
  </div>
</div>

      </div>
      <input type="submit" value="search property" name="filter_search" class="btn">
   </form>
</section>
<!-- search filter section ends -->

<div id="filter-btn" class="fas fa-filter"></div>

<?php
// Initialize properties array
$properties = [];
$search_performed = false;

// Process search queries with prepared statements
if(isset($_POST['h_search'])){
    $search_performed = true;
    $params = [];
    
    // Sanitize inputs
    $h_location = htmlspecialchars($_POST['h_location'] ?? '', ENT_QUOTES);
    $h_type = htmlspecialchars($_POST['h_type'] ?? '', ENT_QUOTES);
    $h_offer = htmlspecialchars($_POST['h_offer'] ?? '', ENT_QUOTES);
    $h_min = (int)($_POST['h_min'] ?? 0);
    $h_max = (int)($_POST['h_max'] ?? PHP_INT_MAX);
    
    // Build query with parameters
    $query = "SELECT * FROM `property` WHERE address LIKE ? AND type LIKE ? AND offer LIKE ? AND price BETWEEN ? AND ? ORDER BY date DESC";
    $params = ["%$h_location%", "%$h_type%", "%$h_offer%", $h_min, $h_max];
    
    $select_properties = $conn->prepare($query);
    $select_properties->execute($params);
    $properties = $select_properties->fetchAll(PDO::FETCH_ASSOC);

}elseif(isset($_POST['filter_search'])){
    $search_performed = true;
    $params = [];
    
    // Sanitize inputs
    $location = htmlspecialchars($_POST['location'] ?? '', ENT_QUOTES);
    $type = htmlspecialchars($_POST['type'] ?? '', ENT_QUOTES);
    $offer = htmlspecialchars($_POST['offer'] ?? '', ENT_QUOTES);
    $bhk = htmlspecialchars($_POST['bhk'] ?? '', ENT_QUOTES);
    $min = (int)($_POST['min'] ?? 0);
    $max = (int)($_POST['max'] ?? PHP_INT_MAX);
    $status = htmlspecialchars($_POST['status'] ?? '', ENT_QUOTES);
    $furnished = htmlspecialchars($_POST['furnished'] ?? '', ENT_QUOTES);
    
    // Build query with parameters
    $query = "SELECT * FROM `property` WHERE address LIKE ? AND type LIKE ? AND offer LIKE ? AND bhk LIKE ? AND status LIKE ? AND furnished LIKE ? AND price BETWEEN ? AND ? ORDER BY date DESC";
    $params = ["%$location%", "%$type%", "%$offer%", "%$bhk%", "%$status%", "%$furnished%", $min, $max];
    
    $select_properties = $conn->prepare($query);
    $select_properties->execute($params);
    $properties = $select_properties->fetchAll(PDO::FETCH_ASSOC);

}else{
    // Default view - latest listings
    $select_properties = $conn->prepare("SELECT * FROM `property` ORDER BY date DESC LIMIT 6");
    $select_properties->execute();
    $properties = $select_properties->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!-- listings section starts -->
<section class="listings">
   <h1 class="heading"><?= $search_performed ? 'search results' : 'latest listings' ?></h1>

   <div class="box-container">
      <?php if(!empty($properties)): ?>
         <?php foreach($properties as $fetch_property): ?>
            <?php
            // Get user info
            $select_user = $conn->prepare("SELECT * FROM `users` WHERE id = ?");
            $select_user->execute([$fetch_property['user_id']]);
            $fetch_user = $select_user->fetch(PDO::FETCH_ASSOC);
            
            // Count images
            $total_images = 1; // image_01 always exists
            for($i = 2; $i <= 5; $i++) {
                if(!empty($fetch_property["image_0$i"])) {
                    $total_images++;
                }
            }
            
            // Check if saved
            $select_saved = $conn->prepare("SELECT * FROM `saved` WHERE property_id = ? AND user_id = ?");
            $select_saved->execute([$fetch_property['id'], $user_id]);
            $is_saved = $select_saved->rowCount() > 0;
            ?>
            
            <form action="" method="POST">
               <input type="hidden" name="property_id" value="<?= htmlspecialchars($fetch_property['id']) ?>">
               <div class="box">
                  <button type="submit" name="save" class="save">
                     <i class="<?= $is_saved ? 'fas' : 'far' ?> fa-heart"></i>
                     <span><?= $is_saved ? 'saved' : 'save' ?></span>
                  </button>
                  <div class="thumb">
                     <p class="total-images"><i class="far fa-image"></i><span><?= $total_images ?></span></p> 
                     <img src="uploaded_files/<?= htmlspecialchars($fetch_property['image_01']) ?>" alt="">
                  </div>
                  <div class="admin">
                     <h3><?= substr(htmlspecialchars($fetch_user['name']), 0, 1) ?></h3>
                     <div>
                        <p><?= htmlspecialchars($fetch_user['name']) ?></p>
                        <span><?= htmlspecialchars($fetch_property['date']) ?></span>
                     </div>
                  </div>
               </div>
               <div class="box">
                  <div class="price"><span>TND &nbsp;</span><span><?= htmlspecialchars($fetch_property['price']) ?></span></div>
                  <h3 class="name"><?= htmlspecialchars($fetch_property['property_name']) ?></h3>
                  <p class="location"><i class="fas fa-map-marker-alt"></i><span><?= htmlspecialchars($fetch_property['address']) ?></span></p>
                  <div class="flex">
                     <p><i class="fas fa-house"></i><span><?= htmlspecialchars($fetch_property['type']) ?></span></p>
                     <p><i class="fas fa-tag"></i><span><?= htmlspecialchars($fetch_property['offer']) ?></span></p>
                     <p><i class="fas fa-bed"></i><span><?= htmlspecialchars($fetch_property['bhk']) ?> BHK</span></p>
                     <p><i class="fas fa-trowel"></i><span><?= htmlspecialchars($fetch_property['status']) ?></span></p>
                     <p><i class="fas fa-couch"></i><span><?= htmlspecialchars($fetch_property['furnished']) ?></span></p>
                     <p><i class="fas fa-maximize"></i><span><?= htmlspecialchars($fetch_property['carpet']) ?>sqft</span></p>
                  </div>
                  <div class="flex-btn">
                     <a href="view_property.php?get_id=<?= htmlspecialchars($fetch_property['id']) ?>" class="btn">view property</a>
                     <input type="submit" value="send enquiry" name="send" class="btn">
                  </div>
               </div>
            </form>
         <?php endforeach; ?>
      <?php else: ?>
         <p class="empty">no results found! <a href="search.php">try different search criteria</a></p>
      <?php endif; ?>
   </div>
</section>
<!-- listings section ends -->

<?php include 'components/footer.php'; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
<script src="js/script.js"></script>
<?php include 'components/message.php'; ?>

<script>
document.querySelector('#filter-btn').onclick = () => {
   document.querySelector('.filters').classList.add('active');
}

document.querySelector('#close-filter').onclick = () => {
   document.querySelector('.filters').classList.remove('active');
}
</script>

</body>
</html>