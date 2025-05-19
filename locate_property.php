<?php  

include 'components/connect.php';

if(isset($_COOKIE['user_id'])){
   $user_id = $_COOKIE['user_id'];
}else{
   $user_id = '';
   header('location:login.php');
}

include 'components/save_send.php';

// Get property ID from URL if available
$property_id = isset($_GET['property_id']) ? $_GET['property_id'] : '';

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Locate Property</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">

   <!-- leaflet css for map -->
   <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" integrity="sha256-kLaT2GOSpHechhsozzB+flnD+zUyjE2LlfWPgU04xyI=" crossorigin="">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">

   <style>
      #map-container {
         width: 100%;
         height: 500px;
         margin-bottom: 2rem;
         border-radius: 10px;
         overflow: hidden;
      }
      #property-map {
         height: 100%;
         width: 100%;
      }
      .map-info {
         background: #fff;
         padding: 15px;
         border-radius: 8px;
         box-shadow: 0 2px 10px rgba(0,0,0,0.1);
         max-width: 350px;
      }
      .map-info h3 {
         margin-top: 0;
         color: var(--main-color);
      }
      .map-info .price {
         font-weight: bold;
         font-size: 1.2rem;
         color: var(--main-color);
         margin-bottom: 10px;
      }
      .map-info .btn {
         margin-top: 10px;
         display: inline-block;
         padding: 8px 15px;
         font-size: 0.9rem;
      }
      .property-list {
         margin-top: 2rem;
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
      .filter-container .btn {
         padding: 0.8rem 1.5rem;
      }
   </style>
</head>
<body>
   
<?php include 'components/user_header.php'; ?>

<section class="property-location">

   <h1 class="heading">locate properties</h1>

   <div class="filter-container">
      <form action="" method="GET" class="search-filter">
         <select name="type" class="input">
            <option value="" selected>property type</option>
            <option value="flat">flat</option>
            <option value="house">house</option>
            <option value="shop">shop</option>
         </select>
         <select name="offer" class="input">
            <option value="" selected>property status</option>
            <option value="sale">sale</option>
            <option value="resale">resale</option>
            <option value="rent">rent</option>
         </select>
         <select name="furnished" class="input">
            <option value="" selected>furnished status</option>
            <option value="furnished">furnished</option>
            <option value="semi-furnished">semi-furnished</option>
            <option value="unfurnished">unfurnished</option>
         </select>
         <input type="submit" value="filter" class="btn">
      </form>
   </div>

   <div id="map-container">
      <div id="property-map"></div>
   </div>

   <div class="box-container property-list">
      <?php
         $where = "";
         $params = [];

         // Add filters if set
         if(isset($_GET['type']) && !empty($_GET['type'])) {
            $where .= "type = ? AND ";
            $params[] = $_GET['type'];
         }
         if(isset($_GET['offer']) && !empty($_GET['offer'])) {
            $where .= "offer = ? AND ";
            $params[] = $_GET['offer'];
         }
         if(isset($_GET['furnished']) && !empty($_GET['furnished'])) {
            $where .= "furnished = ? AND ";
            $params[] = $_GET['furnished'];
         }

         // Add condition for specific property if ID provided
         if(!empty($property_id)) {
            $where .= "id = ? AND ";
            $params[] = $property_id;
         }

         // Complete the query with status check
         $where = $where ? $where . "status != 'sold' ORDER BY date DESC" : "status != 'sold' ORDER BY date DESC";

         $select_properties = $conn->prepare("SELECT * FROM `property` WHERE $where");
         $select_properties->execute($params);
         
         if($select_properties->rowCount() > 0){
            while($fetch_property = $select_properties->fetch(PDO::FETCH_ASSOC)){

            $select_user = $conn->prepare("SELECT * FROM `users` WHERE id = ?");
            $select_user->execute([$fetch_property['user_id']]);
            $fetch_user = $select_user->fetch(PDO::FETCH_ASSOC);

            if(!empty($fetch_property['image_02'])){
               $image_coutn_02 = 1;
            }else{
               $image_coutn_02 = 0;
            }
            if(!empty($fetch_property['image_03'])){
               $image_coutn_03 = 1;
            }else{
               $image_coutn_03 = 0;
            }
            if(!empty($fetch_property['image_04'])){
               $image_coutn_04 = 1;
            }else{
               $image_coutn_04 = 0;
            }
            if(!empty($fetch_property['image_05'])){
               $image_coutn_05 = 1;
            }else{
               $image_coutn_05 = 0;
            }

            $total_images = (1 + $image_coutn_02 + $image_coutn_03 + $image_coutn_04 + $image_coutn_05);

            $select_saved = $conn->prepare("SELECT * FROM `saved` WHERE property_id = ? AND user_id = ?");
            $select_saved->execute([$fetch_property['id'], $user_id]);
      ?>
      <form action="" method="POST" class="property-item" data-lat="<?= $fetch_property['latitude'] ?? '36.8065' ?>" data-lng="<?= $fetch_property['longitude'] ?? '10.1815' ?>" data-id="<?= $fetch_property['id']; ?>" data-name="<?= $fetch_property['property_name']; ?>" data-price="<?= $fetch_property['price']; ?>" data-type="<?= $fetch_property['type']; ?>" data-address="<?= $fetch_property['address']; ?>">
         <input type="hidden" name="property_id" value="<?= $fetch_property['id']; ?>">
         <div class="box">
            <div class="thumb">
               <?php if($select_saved->rowCount() > 0){ ?>
                  <button type="submit" name="save" class="save"><i class="fas fa-heart"></i><span>remove from saved</span></button>
               <?php }else{ ?>
                  <button type="submit" name="save" class="save"><i class="far fa-heart"></i><span>save</span></button>
               <?php } ?>
               <p class="total-images"><i class="far fa-image"></i><span><?= $total_images; ?></span></p> 
               <img src="uploaded_files/<?= $fetch_property['image_01']; ?>" alt="">
            </div>
            <div class="admin">
               <h3><?= substr($fetch_user['name'], 0, 1); ?></h3>
               <div>
                  <p><?= $fetch_user['name']; ?></p>
                  <span><?= $fetch_property['date']; ?></span>
               </div>
            </div>
         </div>
         <div class="box">
            <div class="price"><span>TND &nbsp;</span><span><?= $fetch_property['price']; ?></span></div>
            <h3 class="name"><?= $fetch_property['property_name']; ?></h3>
            <p class="location"><i class="fas fa-map-marker-alt"></i><span><?= $fetch_property['address']; ?></span></p>
            <div class="flex">
               <p><i class="fas fa-house"></i><span><?= $fetch_property['type']; ?></span></p>
               <p><i class="fas fa-tag"></i><span><?= $fetch_property['offer']; ?></span></p>
               <p><i class="fas fa-bed"></i><span><?= $fetch_property['bhk']; ?> BHK</span></p>
               <p><i class="fas fa-trowel"></i><span><?= $fetch_property['status']; ?></span></p>
               <p><i class="fas fa-couch"></i><span><?= $fetch_property['furnished']; ?></span></p>
               <p><i class="fas fa-maximize"></i><span><?= $fetch_property['carpet']; ?>sqft</span></p>
            </div>
            <div class="flex-btn">
               <a href="view_property.php?get_id=<?= $fetch_property['id']; ?>" class="btn">view property</a>
               <input type="submit" value="send enquiry" name="send" class="btn">
            </div>
         </div>
      </form>
      <?php
         }
      }else{
         echo '<p class="empty">no properties found!</p>';
      }
      ?>
   </div>

</section>

<!-- leaflet js for map -->
<script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js" integrity="sha256-WBkoXOwTeyKclOHuWtc+i2uENFpDZ9YPdf5Hf+D7ewM=" crossorigin=""></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>

<?php include 'components/footer.php'; ?>

<!-- custom js file link  -->
<script src="js/script.js"></script>

<script>
   // Initialize map
   const map = L.map('property-map').setView([36.8065, 10.1815], 7); // Default center on Tunisia

   // Add OpenStreetMap tiles
   L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
   }).addTo(map);

   // Add markers for each property
   document.querySelectorAll('.property-item').forEach(property => {
      const lat = parseFloat(property.dataset.lat);
      const lng = parseFloat(property.dataset.lng);
      const id = property.dataset.id;
      const name = property.dataset.name;
      const price = property.dataset.price;
      const type = property.dataset.type;
      const address = property.dataset.address;

      if (!isNaN(lat) && !isNaN(lng)) {
         const marker = L.marker([lat, lng]).addTo(map);
         
         // Create popup content
         const popupContent = `
            <div class="map-info">
               <h3>${name}</h3>
               <div class="price"><span>TND</span> ${price}</div>
               <p><i class="fas fa-map-marker-alt"></i> ${address}</p>
               <p><i class="fas fa-house"></i> ${type}</p>
               <a href="view_property.php?get_id=${id}" class="btn">view details</a>
            </div>
         `;
         
         marker.bindPopup(popupContent);
         
         // If single property view, center and zoom to it
         <?php if(!empty($property_id)) { ?>
            map.setView([lat, lng], 15);
            marker.openPopup();
         <?php } ?>
      }
   });

   // Fit map to bounds if multiple properties
   <?php if(empty($property_id) && $select_properties->rowCount() > 1) { ?>
   setTimeout(() => {
      const markers = document.querySelectorAll('.property-item');
      if (markers.length > 0) {
         const bounds = [];
         markers.forEach(property => {
            const lat = parseFloat(property.dataset.lat);
            const lng = parseFloat(property.dataset.lng);
            if (!isNaN(lat) && !isNaN(lng)) {
               bounds.push([lat, lng]);
            }
         });
         if (bounds.length > 0) {
            map.fitBounds(bounds);
         }
      }
   }, 100);
   <?php } ?>
</script>

<?php include 'components/message.php'; ?>

</body>
</html>