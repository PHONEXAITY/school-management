<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.php">
        <div class="sidebar-brand-icon rotate-n-15">
            <i class="fas fa-school"></i>
        </div>
        <div class="sidebar-brand-text mx-3">School Management</div>
    </a>

    <!-- Divider -->
    <hr class="sidebar-divider my-0">

    <!-- Nav Item - Dashboard -->
    <li class="nav-item <?php echo ($activePage == 'dashboard') ? 'active' : ''; ?>">
        <a class="nav-link" href="index.php">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span></a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">
        School Management
    </div>

    <!-- Nav Item - Students Collapse Menu -->
    <li class="nav-item <?php echo (strpos($activePage, 'student') !== false) ? 'active' : ''; ?>">
        <a class="nav-link <?php echo (strpos($activePage, 'student') !== false) ? '' : 'collapsed'; ?>" href="#"
            data-toggle="collapse" data-target="#collapseStudents"
            aria-expanded="<?php echo (strpos($activePage, 'student') !== false) ? 'true' : 'false'; ?>"
            aria-controls="collapseStudents">
            <i class="fas fa-fw fa-user-graduate"></i>
            <span>Students</span>
        </a>
        <div id="collapseStudents"
            class="collapse <?php echo (strpos($activePage, 'student') !== false) ? 'show' : ''; ?>"
            aria-labelledby="headingStudents" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Student Options:</h6>
                <a class="collapse-item <?php echo ($activePage == 'students') ? 'active' : ''; ?>"
                    href="students.php">Students</a>
            </div>
        </div>
    </li>

    <!-- Nav Item - Teachers Collapse Menu -->
    <li class="nav-item <?php echo (strpos($activePage, 'teacher') !== false) ? 'active' : ''; ?>">
        <a class="nav-link <?php echo (strpos($activePage, 'teacher') !== false) ? '' : 'collapsed'; ?>" href="#"
            data-toggle="collapse" data-target="#collapseTeachers"
            aria-expanded="<?php echo (strpos($activePage, 'teacher') !== false) ? 'true' : 'false'; ?>"
            aria-controls="collapseTeachers">
            <i class="fas fa-fw fa-chalkboard-teacher"></i>
            <span>Teachers</span>
        </a>
        <div id="collapseTeachers"
            class="collapse <?php echo (strpos($activePage, 'teacher') !== false) ? 'show' : ''; ?>"
            aria-labelledby="headingTeachers" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Teacher Options:</h6>
                <a class="collapse-item <?php echo ($activePage == 'teachers') ? 'active' : ''; ?>"
                    href="teachers.php">Teachers</a>

            </div>
        </div>
    </li>

    <!-- Nav Item - Classes Collapse Menu -->
    <!-- Nav Item - Classes Collapse Menu -->
    <li
        class="nav-item <?php echo in_array($activePage, ['levels', 'classes', 'subjects', 'terms', 'years']) ? 'active' : ''; ?>">
        <a class="nav-link <?php echo in_array($activePage, ['levels', 'classes', 'subjects', 'terms', 'years']) ? '' : 'collapsed'; ?>"
            href="#" data-toggle="collapse" data-target="#collapseClasses"
            aria-expanded="<?php echo in_array($activePage, ['levels', 'classes', 'subjects', 'terms', 'years']) ? 'true' : 'false'; ?>"
            aria-controls="collapseClasses">
            <i class="fas fa-fw fa-chalkboard"></i>
            <span>School_info</span>
        </a>
        <div id="collapseClasses"
            class="collapse <?php echo in_array($activePage, ['levels', 'classes', 'subjects', 'terms', 'years']) ? 'show' : ''; ?>"
            aria-labelledby="headingClasses" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Class Options:</h6>
                <a class="collapse-item <?php echo ($activePage == 'levels') ? 'active' : ''; ?>"
                    href="levels.php">Levels</a>
                <a class="collapse-item <?php echo ($activePage == 'classes') ? 'active' : ''; ?>"
                    href="classes.php">Classes</a>
                <a class="collapse-item <?php echo ($activePage == 'subjects') ? 'active' : ''; ?>"
                    href="subjects.php">Subject</a>
                <a class="collapse-item <?php echo ($activePage == 'terms') ? 'active' : ''; ?>"
                    href="terms.php">Term</a>
                <a class="collapse-item <?php echo ($activePage == 'school-year') ? 'active' : ''; ?>"
                    href="years.php">school year</a>
            </div>
        </div>
    </li>

    <!-- Nav Item - Attendance Collapse Menu -->
    <li class="nav-item <?php echo (strpos($activePage, 'scores') !== false) ? 'active' : ''; ?>">
        <a class="nav-link <?php echo (strpos($activePage, 'scores') !== false) ? '' : 'collapsed'; ?>" href="#"
            data-toggle="collapse" data-target="#collapseAttendance"
            aria-expanded="<?php echo (strpos($activePage, 'scores') !== false) ? 'true' : 'false'; ?>"
            aria-controls="collapseAttendance">
            <i class="fas fa-fw fa-calendar-check"></i>
            <span>Scores</span>
        </a>
        <div id="collapseAttendance"
            class="collapse <?php echo (strpos($activePage, 'scores') !== false) ? 'show' : ''; ?>"
            aria-labelledby="headingAttendance" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Score Options:</h6>
                <a class="collapse-item <?php echo ($activePage == 'scores') ? 'active' : ''; ?>" href="scores.php">Add
                    Scores</a>
                <a class="collapse-item <?php echo ($activePage == 'view_scores') ? 'active' : ''; ?>"
                    href="view_scores.php">View Score</a>
                <a class="collapse-item <?php echo ($activePage == 'scores_report') ? 'active' : ''; ?>"
                    href="score_report.php">Reports</a>
            </div>
        </div>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">
        Administration
    </div>

    <!-- Nav Item - Users Collapse Menu -->
    <li class="nav-item <?php echo (strpos($activePage, 'user') !== false) ? 'active' : ''; ?>">
        <a class="nav-link <?php echo (strpos($activePage, 'user') !== false) ? '' : 'collapsed'; ?>" href="#"
            data-toggle="collapse" data-target="#collapseUsers"
            aria-expanded="<?php echo (strpos($activePage, 'user') !== false) ? 'true' : 'false'; ?>"
            aria-controls="collapseUsers">
            <i class="fas fa-fw fa-users"></i>
            <span>Users</span>
        </a>
        <div id="collapseUsers" class="collapse <?php echo (strpos($activePage, 'user') !== false) ? 'show' : ''; ?>"
            aria-labelledby="headingUsers" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">User Options:</h6>
                <a class="collapse-item <?php echo ($activePage == 'users') ? 'active' : ''; ?>" href="user.php">
                    Users</a>
            </div>
        </div>
    </li>

    <!-- Nav Item - Register Collapse Menu -->
    <li class="nav-item <?php echo (strpos($activePage, 'setting') !== false) ? 'active' : ''; ?>">
        <a class="nav-link <?php echo (strpos($activePage, 'setting') !== false) ? '' : 'collapsed'; ?>" href="#"
            data-toggle="collapse" data-target="#collapseSettings"
            aria-expanded="<?php echo (strpos($activePage, 'setting') !== false) ? 'true' : 'false'; ?>"
            aria-controls="collapseSettings">
            <i class="fas fa-fw fa-cogs"></i>
            <span>Register</span>
        </a>
        <div id="collapseSettings"
            class="collapse <?php echo (strpos($activePage, 'setting') !== false) ? 'show' : ''; ?>"
            aria-labelledby="headingSettings" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Setting Options:</h6>
                <a class="collapse-item <?php echo ($activePage == 'settings') ? 'active' : ''; ?>"
                    href="settings.php">All Register</a>
                <a class="collapse-item <?php echo ($activePage == 'setting-school') ? 'active' : ''; ?>"
                    href="setting-school.php">Expire Registet</a>
                <a class="collapse-item <?php echo ($activePage == 'setting-academic') ? 'active' : ''; ?>"
                    href="setting-academic.php">Reject</a>

            </div>
        </div>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider d-none d-md-block">

    <!-- Sidebar Toggler (Sidebar) -->
    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>

</ul>