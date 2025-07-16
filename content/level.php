<?php
$queryLevel = mysqli_query($config, "SELECT * FROM level WHERE deleted_at IS NULL ORDER BY id DESC");
$rowLevel = mysqli_fetch_all($queryLevel, MYSQLI_ASSOC);

if (isset($_GET['delete'])) {
    $id_level = $_GET['delete'];
    $now = date('Y-m-d H:i:s');
    mysqli_query($config, "UPDATE level SET deleted_at = '$now' WHERE id='$id_level'");
    header("location:?page=level&hapus=berhasil");
}
?>
<section class="section">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Data Level</h5>
                    <div class="table-responsive">
                        <div class="mb-3" align="right">
                            <a href="?page=tambah-level" class="btn btn-primary">Add Level</a>
                        </div>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Level</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rowLevel as $index => $level): ?>
                                    <tr>
                                        <td><?php echo $index += 1 ?></td>
                                        <td><?php echo $level['level_name'] ?></td>
                                        <td>
                                            <a href="?page=tambah-level&edit=<?php echo $level['id'] ?>" class="btn btn-warning">Edit</a>
                                            <a href="?page=level&delete=<?php echo $level['id'] ?>" class="btn btn-danger">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>