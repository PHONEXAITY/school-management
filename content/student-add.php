<!-- Page Heading -->
<h1 class="h3 mb-2 text-gray-800">Add New Student</h1>
<p class="mb-4">Fill in the form below to add a new student to the system.</p>

<!-- Add Student Form -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Student Information</h6>
    </div>
    <div class="card-body">
        <form method="post" action="process-student-add.php" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="fullname" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Student ID <span class="text-danger">*</span></label>
                        <input type="text" name="student_id" class="form-control" required>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Class <span class="text-danger">*</span></label>
                        <select name="class_id" class="form-control" required>
                            <option value="">Select Class</option>
                            <option value="1">10th Grade</option>
                            <option value="2">11th Grade</option>
                            <option value="3">12th Grade</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Roll No <span class="text-danger">*</span></label>
                        <input type="text" name="roll_no" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Gender <span class="text-danger">*</span></label>
                        <select name="gender" class="form-control" required>
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Date of Birth <span class="text-danger">*</span></label>
                        <input type="date" name="dob" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Phone <span class="text-danger">*</span></label>
                        <input type="tel" name="phone" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Photo</label>
                        <input type="file" name="photo" class="form-control-file">
                        <small class="text-muted">Upload student photo (optional)</small>
                    </div>
                </div>
            </div>

            <hr>
            <h5 class="text-primary">Parent/Guardian Information</h5>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Parent/Guardian Name <span class="text-danger">*</span></label>
                        <input type="text" name="parent_name" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Relationship</label>
                        <select name="relationship" class="form-control">
                            <option value="Father">Father</option>
                            <option value="Mother">Mother</option>
                            <option value="Guardian">Guardian</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Parent/Guardian Phone <span class="text-danger">*</span></label>
                        <input type="tel" name="parent_phone" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Parent/Guardian Email</label>
                        <input type="email" name="parent_email" class="form-control">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
            </div>

            <div class="form-group mt-4">
                <button type="submit" name="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Student
                </button>
                <a href="students.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>
