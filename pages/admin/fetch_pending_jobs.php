<?php
session_start();
if (!isset($_SESSION['admin_id'], $_SESSION['admin_username'])) {
    http_response_code(403);
    exit('Forbidden');
}
include '../../db_connection/connection.php';
$conn = OpenConnection();

$query = "SELECT j.job_id, j.employer_id, j.title, j.description, j.category, j.salary, j.location, j.status, j.created_at, j.company_name, j.skills, j.education, u.company_image
          FROM `jobs` j
          JOIN `users` u ON j.employer_id = u.user_id
          WHERE j.status = 'pending'
          ORDER BY j.created_at DESC";
$result = $conn->query($query);

$pendingJobs = [];
while ($row = $result->fetch_assoc()) {
    $pendingJobs[] = $row;
}
if (!empty($pendingJobs)): ?>
    <ul class="job-list">
        <?php foreach ($pendingJobs as $job): ?>
            <li class="job-card"
                data-job-id="<?php echo htmlspecialchars($job['job_id']); ?>"
                data-title="<?php echo htmlspecialchars($job['title']); ?>"
                data-category="<?php echo htmlspecialchars($job['category']); ?>"
                data-company="<?php echo htmlspecialchars($job['company_name']); ?>"
                data-logo="<?php
                    $img = $job['company_image'];
                    echo (file_exists($img) && !empty($img)) ? htmlspecialchars($img) : '../../static/img/company_img/default.jpg';
                ?>"
                data-employer="<?php echo htmlspecialchars($job['employer_id']); ?>"
                data-desc="<?php echo htmlspecialchars($job['description']); ?>"
                data-salary="<?php echo htmlspecialchars($job['salary']); ?>"
                data-location="<?php echo htmlspecialchars($job['location']); ?>"
                data-created="<?php echo htmlspecialchars($job['created_at']); ?>"
                data-skills="<?php echo htmlspecialchars($job['skills']); ?>"
                data-education="<?php echo htmlspecialchars($job['education']); ?>"
            >
                <img class="company-logo" src="<?php
                    $img = $job['company_image'];
                    echo (file_exists($img) && !empty($img)) ? htmlspecialchars($img) : '../../static/img/company_img/default.jpg';
                ?>" alt="Company Logo">
                <div class="job-card-content">
                    <div class="job-title"><?php echo htmlspecialchars($job['title']); ?></div>
                    <div class="job-meta">
                        Category: <?php echo htmlspecialchars($job['category']); ?>
                    </div>
                    <div class="company-name">
                        <a href="view_job_details_admin.php?company_id=<?php echo htmlspecialchars($job['employer_id']); ?>" class="btn-company" style="text-decoration:none;" onclick="event.stopPropagation();">
                            <?php echo htmlspecialchars($job['company_name']); ?>
                        </a>
                    </div>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
    <p class="no-jobs">No pending job posts found.</p>
<?php endif; ?>
