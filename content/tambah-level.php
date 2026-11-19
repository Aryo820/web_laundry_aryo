<?php
if (isset($_GET['edit'])) {
    $edit = $_GET['edit'];
    $query = mysqli_query($config, "SELECT * FROM level WHERE id='$edit'");
    $row = mysqli_fetch_assoc($query);

    if (isset($_POST['save'])) {
        $level_name = $_POST['level_name'];

        mysqli_query($config, "UPDATE level SET level_name ='$level_name' WHERE id='$edit'");
        header("location:?page=level&ubah=berhasil");
        exit;
    }
} else {
    if (isset($_POST['save'])) {
        $level_name = $_POST['level_name'];

        mysqli_query($config, "INSERT INTO level (level_name) VALUES ('$level_name')");
        header("location:?page=level&tambah=berhasil");
        exit;
    }
}
?>
<section class="section">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><?php echo isset($_GET['edit']) ? 'Edit' : 'Add' ?> Level</h5>
                    <form action="" method="post">
                        <div class="mb-3">
                            <label for="" class="form-label">Name Level</label>
                            <input name="level_name" type="text" class="form-control" value="<?php echo isset($_GET['edit']) ? $row['level_name'] : '' ?>" placeholder="Enter your name level" required>
                        </div>
                        <button name="save" type="submit" class="btn btn-success">Save</button>
                </div>
                </form>
            </div>
        </div>
    </div>
    </div>
</section>