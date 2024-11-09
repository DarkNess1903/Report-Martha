<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>บริษัท มาร์ธา กรุ๊ปจำกัด</title>
  <meta content="" name="description">
  <meta content="" name="keywords">

  <!-- Favicons -->
  <link href="assets/img/ma2.png" rel="icon">
  <link href="assets/img/ma2.png" rel="apple-touch-icon">

  <!-- Google Fonts -->
  <link href="https://fonts.gstatic.com" rel="preconnect">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
  <link href="assets/vendor/quill/quill.snow.css" rel="stylesheet">
  <link href="assets/vendor/quill/quill.bubble.css" rel="stylesheet">
  <link href="assets/vendor/remixicon/remixicon.css" rel="stylesheet">
  <link href="assets/vendor/simple-datatables/style.css" rel="stylesheet">

  <!-- Template Main CSS File -->
  <link href="assets/css/style.css" rel="stylesheet">

</head>

<!-- เนื้อหา -->
<main id="main" class="main">
<div class="col-lg-12">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">แสดงกราฟยอดรวมของทุกคนปีปัจจุบัน ของทุกคน
              </h5>

              <!-- Bar Chart -->
              <canvas id="barChart" style="max-height: 400px;"></canvas>
              <script>
                document.addEventListener("DOMContentLoaded", () => {
                  new Chart(document.querySelector('#barChart'), {
                    type: 'bar',
                    data: {
                      labels: ['January', 'February', 'March', 'April', 'May', 'June', 'July','August','September','October','November','December'],
                      datasets: [{
                        label: 'Bar Chart',
                        data: [65, 59, 80, 81, 56, 55, 40, 50, 30, 28 ,82,65],
                        backgroundColor: [
                          '#f9bdbb', 
                          '#f8bbd0', 
                          '#e1bee7', 
                          '#d1c4e9',
                          '#d0d9ff',
                          '#b3e5fc',
                          '#b2ebf2',
                          '#b2dfdb',
                          '#a3e9a4',
                          '#dcedc8',
                          '#fff9c4',
                          '#ffe0b2'                        
                        
                        ],
                        borderColor: [
                          '#e51c23',
                          '#e91e63',
                          '#9c27b0',
                          '#673ab7',
                          '#5677fc',
                          '#03a9f4',
                          '#00bcd4',
                          '#009688',
                          '#259b24',
                          '#8bc34a',
                          '#ffeb3b',
                          '#ff9800'

                        ],
                        borderWidth: 1
                      }]
                    },
                    options: {
                      scales: {
                        y: {
                          beginAtZero: true
                        }
                      }
                    }
                  });
                });
              </script>
              <!-- End Bar CHart -->
            </div>
          </div>
        </div>

      

</main>
<!-- จบเนื้อหา -->

  <?php include 'navbar.php'; ?> 
</html>