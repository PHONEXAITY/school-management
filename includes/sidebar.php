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
                    href="students.php">All Students</a>
                <a class="collapse-item <?php echo ($activePage == 'student-add') ? 'active' : ''; ?>"
                    href="student-add.php">Add Student</a>
                <a class="collapse-item <?php echo ($activePage == 'student-attendance') ? 'active' : ''; ?>"
                    href="student-attendance.php">Attendance</a>
                <a class="collapse-item <?php echo ($activePage == 'student-reports') ? 'active' : ''; ?>"
                    href="student-reports.php">Reports</a>
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
                    href="teachers.php">All Teachers</a>
                <a class="collapse-item <?php echo ($activePage == 'teacher-add') ? 'active' : ''; ?>"
                    href="teacher-add.php">Add Teacher</a>
                <a class="collapse-item <?php echo ($activePage == 'teacher-attendance') ? 'active' : ''; ?>"
                    href="teacher-attendance.php">Attendance</a>
                <a class="collapse-item <?php echo ($activePage == 'teacher-schedule') ? 'active' : ''; ?>"
                    href="teacher-schedule.php">Schedule</a>
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
    <li class="nav-item <?php echo (strpos($activePage, 'attendance') !== false) ? 'active' : ''; ?>">
        <a class="nav-link <?php echo (strpos($activePage, 'attendance') !== false) ? '' : 'collapsed'; ?>" href="#"
            data-toggle="collapse" data-target="#collapseAttendance"
            aria-expanded="<?php echo (strpos($activePage, 'attendance') !== false) ? 'true' : 'false'; ?>"
            aria-controls="collapseAttendance">
            <i class="fas fa-fw fa-calendar-check"></i>
            <span>Attendance</span>
        </a>
        <div id="collapseAttendance"
            class="collapse <?php echo (strpos($activePage, 'attendance') !== false) ? 'show' : ''; ?>"
            aria-labelledby="headingAttendance" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Attendance Options:</h6>
                <a class="collapse-item <?php echo ($activePage == 'attendance') ? 'active' : ''; ?>"
                    href="attendance.php">Overview</a>
                <a class="collapse-item <?php echo ($activePage == 'attendance-student') ? 'active' : ''; ?>"
                    href="attendance-student.php">Student Attendance</a>
                <a class="collapse-item <?php echo ($activePage == 'attendance-teacher') ? 'active' : ''; ?>"
                    href="attendance-teacher.php">Teacher Attendance</a>
                <a class="collapse-item <?php echo ($activePage == 'attendance-reports') ? 'active' : ''; ?>"
                    href="attendance-reports.php">Reports</a>
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
                <a class="collapse-item <?php echo ($activePage == 'users') ? 'active' : ''; ?>" href="users.php">All
                    Users</a>
                <a class="collapse-item <?php echo ($activePage == 'user-add') ? 'active' : ''; ?>"
                    href="user-add.php">Add User</a>
                <a class="collapse-item <?php echo ($activePage == 'user-roles') ? 'active' : ''; ?>"
                    href="user-roles.php">Roles</a>
                <a class="collapse-item <?php echo ($activePage == 'user-permissions') ? 'active' : ''; ?>"
                    href="user-permissions.php">Permissions</a>
            </div>
        </div>
    </li>

    <!-- Nav Item - Settings Collapse Menu -->
    <li class="nav-item <?php echo (strpos($activePage, 'setting') !== false) ? 'active' : ''; ?>">
        <a class="nav-link <?php echo (strpos($activePage, 'setting') !== false) ? '' : 'collapsed'; ?>" href="#"
            data-toggle="collapse" data-target="#collapseSettings"
            aria-expanded="<?php echo (strpos($activePage, 'setting') !== false) ? 'true' : 'false'; ?>"
            aria-controls="collapseSettings">
            <i class="fas fa-fw fa-cogs"></i>
            <span>Settings</span>
        </a>
        <div id="collapseSettings"
            class="collapse <?php echo (strpos($activePage, 'setting') !== false) ? 'show' : ''; ?>"
            aria-labelledby="headingSettings" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Setting Options:</h6>
                <a class="collapse-item <?php echo ($activePage == 'settings') ? 'active' : ''; ?>"
                    href="settings.php">General Settings</a>
                <a class="collapse-item <?php echo ($activePage == 'setting-school') ? 'active' : ''; ?>"
                    href="setting-school.php">School Info</a>
                <a class="collapse-item <?php echo ($activePage == 'setting-academic') ? 'active' : ''; ?>"
                    href="setting-academic.php">Academic Year</a>
                <a class="collapse-item <?php echo ($activePage == 'setting-appearance') ? 'active' : ''; ?>"
                    href="setting-appearance.php">Appearance</a>
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