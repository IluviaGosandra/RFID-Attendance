<?php
// Include config file
require_once "config.php";

// Pagination
if (isset($_GET['pageno'])) {
    $pageno = $_GET['pageno'];
} else {
    $pageno = 1;
}
$no_of_records_per_page = 10;
$offset = ($pageno - 1) * $no_of_records_per_page;

// Total Pages
$total_pages_sql = "SELECT COUNT(DISTINCT data_absen.uid, tanggal) AS total FROM data_absen";
$result = mysqli_query($link, $total_pages_sql);
$total_rows = mysqli_fetch_assoc($result)['total'];
$total_pages = ceil($total_rows / $no_of_records_per_page);

// Column sorting
$orderBy = array('tanggal', 'uid', 'nama'); 
$order = 'nama';
if (isset($_GET['order']) && in_array($_GET['order'], $orderBy)) {
    $order = $_GET['order'];
}

// Column sort order
$sortBy = array('asc', 'desc'); 
$sort = 'desc';
if (isset($_GET['sort']) && in_array($_GET['sort'], $sortBy)) {
    $sort = ($_GET['sort'] == 'asc') ? 'desc' : 'asc';
}

// Query utama untuk menampilkan data
$sql = "SELECT data_absen.uid, tanggal, nama, division,
         MIN(CASE WHEN status='IN' THEN waktu END) AS jam_masuk,
         MAX(CASE WHEN status='OUT' THEN waktu END) AS jam_keluar
      FROM data_absen 
      JOIN data_karyawan ON data_absen.uid = data_karyawan.uid 
      GROUP BY data_absen.uid, tanggal, nama, division
      ORDER BY $order $sort 
      LIMIT $offset, $no_of_records_per_page";

// Pencarian data
if (!empty($_GET['search'])) {
    $search = $_GET['search'];
    $sql = "SELECT data_absen.uid, tanggal, nama, division,
             MIN(CASE WHEN status='IN' THEN waktu END) AS jam_masuk,
             MAX(CASE WHEN status='OUT' THEN waktu END) AS jam_keluar
          FROM data_absen
          JOIN data_karyawan ON data_absen.uid = data_karyawan.uid 
          WHERE CONCAT(tanggal, data_absen.uid, nama) LIKE '%$search%'
          GROUP BY data_absen.uid, tanggal, nama, division
          ORDER BY $order $sort 
          LIMIT $offset, $no_of_records_per_page";
} else {
    $search = "";
}

$result = mysqli_query($link, $sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>HMIF - Dashboard</title>

  <!-- Custom fonts and styles -->
  <link href="../src/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
  <link href="../src/css/sb-admin-2.min.css" rel="stylesheet">
  <link href="../src/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
  
  <script src="../src/vendor/jquery/jquery.min.js"></script>
  <script src="../src/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script type="text/javascript">
      $(document).ready(function(){
          $('[data-toggle="tooltip"]').tooltip();
      });
  </script>
</head>

<body id="page-top">
  <div id="wrapper">
    <?php include 'partial_sidebar.php'; ?>

    <div id="content-wrapper" class="d-flex flex-column">
      <div id="content">
        <?php include 'partial_topbar.php'; ?>

        <div class="container-fluid">
          <h1 class="h3 mb-2 text-gray-800">Data Absensi</h1>

          <div class="card shadow mb-4">
            <div class="card-header py-3">
              <h6 class="m-0 font-weight-bold text-primary">Data Absensi Harian</h6>
            </div>
            <div class="card-body">
              <div class="col-md-12">
                <div class="row">
                  <div class="col-md-6">
                    <a href="#" class="btn btn-success pull-right disabled">Tambah Data Absensi</a>
                  </div>
                  <div class="col-md-6">
                    <form action="data_absen-index.php" method="get">
                      <div class="col">
                        <input type="text" class="form-control" placeholder="Pencarian data absensi" name="search" value="<?php echo $search; ?>">
                      </div>
                    </form>
                  </div>
                </div>
                <br>

                <?php
                if ($result && mysqli_num_rows($result) > 0) {
                    echo "<table class='table table-bordered table-striped'>";
                    echo "<thead>";
                    echo "<tr>";
                    echo "<th><a href='?search=$search&order=tanggal&sort=$sort'>Tanggal</a></th>";
                    echo "<th><a href='?search=$search&order=uid&sort=$sort'>UID</a></th>";
                    echo "<th><a href='?search=$search&order=nama&sort=$sort'>Nama</a></th>";
                    echo "<th>Jam Masuk</th>";
                    echo "<th>Jam Keluar</th>";
                    echo "<th>Action</th>";
                    echo "</tr>";
                    echo "</thead>";
                    echo "<tbody>";
                    while ($row = mysqli_fetch_array($result)) {
                        echo "<tr>";
                        echo "<td>" . $row['tanggal'] . "</td>";
                        echo "<td>" . $row['uid'] . "</td>";
                        echo "<td>" . $row['nama'] . "</td>";
                        echo "<td>" . $row['jam_masuk'] . "</td>";
                        echo "<td>" . $row['jam_keluar'] . "</td>";
                        echo "<td>";
                        echo "<a href='#' title='Edit' data-toggle='tooltip'><span class='fa fa-edit'></span></a> &nbsp";
                        echo "<a href='#' title='Hapus' data-toggle='tooltip'><span class='fa fa-trash'></span></a>";
                        echo "</td>";
                        echo "</tr>";
                    }
                    echo "</tbody>";
                    echo "</table>";
                } else {
                    echo "<p class='lead'><em>No records found.</em></p>";
                }
                ?>

                <nav aria-label="Page navigation">
                  <ul class="pagination">
                    <li class="page-item"><a class="page-link" href="?pageno=1">First</a></li>
                    <li class="page-item <?php if ($pageno <= 1) echo 'disabled'; ?>">
                      <a class="page-link" href="<?php if ($pageno > 1) echo "?pageno=" . ($pageno - 1); else echo '#'; ?>">Prev</a>
                    </li>
                    <li class="page-item <?php if ($pageno >= $total_pages) echo 'disabled'; ?>">
                      <a class="page-link" href="<?php if ($pageno < $total_pages) echo "?pageno=" . ($pageno + 1); else echo '#'; ?>">Next</a>
                    </li>
                    <li class="page-item"><a class="page-link" href="?pageno=<?php echo $total_pages; ?>">Last</a></li>
                  </ul>
                </nav>

              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</body>
</html>
