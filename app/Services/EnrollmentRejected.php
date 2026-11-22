<?php

namespace App\Services;

use RuntimeException;

/**
 * Thrown when a business rule refuses an enrollment. The message is shown to the student.
 */
final class EnrollmentRejected extends RuntimeException {}
