<!-- Page Heading -->
<h1 class="h3 mb-2 text-gray-800">Add New Teacher</h1>
<p class="mb-4">Fill in the form below to add a new teacher to the system.</p>

<!-- Add Teacher Form -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Teacher Information</h6>
    </div>
    <div class="card-body">
        <form method="post" action="process-teacher-add.php" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="fullname" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Teacher ID <span class="text-danger">*</span></label>
                        <input type="text" name="teacher_id" class="form-control" required>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Department <span class="text-danger">*</span></label>
                        <select name="department_id" class="form-control" required>
                            <option value="">Select Department</option>
                            <option value="1">Mathematics</option>
                            <option value="2">Science</option>
                            <option value="3">Languages</option>
                            <option value="4">Social Studies</option>
                            <option value="5">Arts</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Designation <span class="text-danger">*</span></label>
                        <select name="designation" class="form-control" required>
                            <option value="">Select Designation</option>
                            <option value="Teacher">Teacher</option>
                            <option value="Senior Teacher">Senior Teacher</option>
                            <option value="Head of Department">Head of Department</option>
                            <option value="Assistant Principal">Assistant Principal</option>
                            <option value="Principal">Principal</option>
                        </select>
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
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Joining Date <span class="text-danger">*</span></label>
                        <input type="date" name="joining_date" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Qualification <span class="text-danger">*</span></label>
                        <input type="text" name="qualification" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Salary</label>
                        <input type="number" name="salary" class="form-control">
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
                        <small class="text-muted">Upload teacher photo (optional)</small>
                    </div>
                </div>
            </div>

            <hr>
            <h5 class="text-primary">Subjects Teaching</h5>
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <div class="custom-control custom-checkbox custom-control-inline">
                            <input type="checkbox" class="custom-control-input" id="subject1" name="subjects[]" value="1">
                            <label class="custom-control-label" for="subject1">Mathematics</label>
                        </div>
                        <div class="custom-control custom-checkbox custom-control-inline">
                            <input type="checkbox" class="custom-control-input" id="subject2" name="subjects[]" value="2">
                            <label class="custom-control-label" for="subject2">Physics</label>
                        </div>
                        <div class="custom-control custom-checkbox custom-control-inline">
                            <input type="checkbox" class="custom-control-input" id="subject3" name="subjects[]" value="3">
                            <label class="custom-control-label" for="subject3">Chemistry</label>
                        </div>
                        <div class="custom-control custom-checkbox custom-control-inline">
                            <input type="checkbox" class="custom-control-input" id="subject4" name="subjects[]" value="4">
                            <label class="custom-control-label" for="subject4">Biology</label>
                        </div>
                        <div class="custom-control custom-checkbox custom-control-inline">
                            <input type="checkbox" class="custom-control-input" id="subject5" name="subjects[]" value="5">
                            <label class="custom-control-label" for="subject5">English</label>
                        </div>
                        <div class="custom-control custom-checkbox custom-control-inline">
                            <input type="checkbox" class="custom-control-input" id="subject6" name="subjects[]" value="6">
                            <label class="custom-control-label" for="subject6">History</label>
                        </div>
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
                    <i class="fas fa-save"></i> Save Teacher
                </button>
                <a href="teachers.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>
