<!-- Page Heading -->
<h1 class="h3 mb-2 text-gray-800">Teachers Management</h1>
<p class="mb-4">Manage all your teachers records here. You can add, edit, view, and delete teacher details.</p>

<!-- DataTales Example -->
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">Teachers</h6>
        <a href="teacher-add.php" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Add New Teacher
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Designation</th>
                        <th>Phone</th>
                        <th>Joining Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Designation</th>
                        <th>Phone</th>
                        <th>Joining Date</th>
                        <th>Actions</th>
                    </tr>
                </tfoot>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td>John Smith</td>
                        <td>Mathematics</td>
                        <td>Senior Teacher</td>
                        <td>+66 912345678</td>
                        <td>2020-08-15</td>
                        <td>
                            <a href="#" class="btn btn-info btn-circle btn-sm" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="#" class="btn btn-warning btn-circle btn-sm" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="#" class="btn btn-danger btn-circle btn-sm" title="Delete">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td>Sarah Johnson</td>
                        <td>Science</td>
                        <td>Head of Department</td>
                        <td>+66 923456789</td>
                        <td>2018-06-10</td>
                        <td>
                            <a href="#" class="btn btn-info btn-circle btn-sm" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="#" class="btn btn-warning btn-circle btn-sm" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="#" class="btn btn-danger btn-circle btn-sm" title="Delete">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td>3</td>
                        <td>David Williams</td>
                        <td>Languages</td>
                        <td>Teacher</td>
                        <td>+66 934567890</td>
                        <td>2021-02-20</td>
                        <td>
                            <a href="#" class="btn btn-info btn-circle btn-sm" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="#" class="btn btn-warning btn-circle btn-sm" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="#" class="btn btn-danger btn-circle btn-sm" title="Delete">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
