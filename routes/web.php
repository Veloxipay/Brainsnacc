<?php

use App\Http\Controllers\Admin\LessonController as AdminLessonController;
use App\Http\Controllers\Admin\QuestionController as AdminQuestionController;
use App\Http\Controllers\Admin\StructureController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\MockExamController;
use App\Http\Controllers\ParentController;
use App\Http\Controllers\PastPaperController;
use Illuminate\Support\Facades\Route;

// Login and public sign-up (parents, teachers and students)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:5,1');
});
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/account', [AccountController::class, 'edit'])->name('account');
    Route::put('/account', [AccountController::class, 'update'])->name('account.update');
    // Sends each person to the right home page for their role
    Route::get('/', [DashboardController::class, 'home'])->name('home');

    // Student area (admins can look too, to check content)
    Route::middleware('role:student,admin')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'student'])->name('dashboard');
        Route::get('/course/{subject}', [CourseController::class, 'show'])->name('course');
        Route::get('/lessons/{lesson}', [LessonController::class, 'show'])->name('lessons.show');
        Route::post('/lessons/{lesson}/complete', [LessonController::class, 'complete'])->name('lessons.complete');
        Route::post('/questions/{question}/answer', [LessonController::class, 'answer'])->name('questions.answer');
        Route::post('/questions/{question}/steps', [LessonController::class, 'steps'])->middleware('throttle:20,1')->name('questions.steps');
        Route::get('/questions/{question}/help', [LessonController::class, 'help'])->name('questions.help');
        Route::get('/past-papers', [PastPaperController::class, 'index'])->name('past-papers');
        Route::post('/past-papers', [PastPaperController::class, 'update'])->name('past-papers.update');
        Route::get('/practice-exams/{slug}', [MockExamController::class, 'show'])->name('mock-exams.show');
        Route::post('/practice-exams/{slug}/start', [MockExamController::class, 'start'])->name('mock-exams.start');
        Route::get('/practice-exams/sitting/{attempt}', [MockExamController::class, 'attempt'])->name('mock-exams.attempt');
        Route::post('/practice-exams/sitting/{attempt}/submit', [MockExamController::class, 'submit'])->name('mock-exams.submit');
        Route::post('/practice-exams/sitting/{attempt}/mark', [MockExamController::class, 'mark'])->name('mock-exams.mark');
    });

    // Parent and teacher area
    Route::middleware('role:parent,teacher,admin')->group(function () {
        Route::get('/family', [ParentController::class, 'index'])->name('family');
        Route::get('/family/students/create', [ParentController::class, 'create'])->name('family.students.create');
        Route::post('/family/students', [ParentController::class, 'store'])->name('family.students.store');
        Route::get('/family/students/{student}', [ParentController::class, 'show'])->name('family.students.show');
        Route::put('/family/students/{student}', [ParentController::class, 'update'])->name('family.students.update');
        Route::delete('/family/students/{student}', [ParentController::class, 'destroy'])->name('family.students.destroy');
    });

    // Admin area
    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [AdminUserController::class, 'create'])->name('users.create');
        Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');

        Route::get('/structure', [StructureController::class, 'index'])->name('structure');
        Route::post('/units', [StructureController::class, 'storeUnit'])->name('units.store');
        Route::put('/units/{unit}', [StructureController::class, 'updateUnit'])->name('units.update');
        Route::post('/units/{unit}/modules', [StructureController::class, 'storeModule'])->name('modules.store');
        Route::put('/modules/{module}', [StructureController::class, 'updateModule'])->name('modules.update');

        Route::get('/lessons', [AdminLessonController::class, 'index'])->name('lessons.index');
        Route::get('/lessons/create', [AdminLessonController::class, 'create'])->name('lessons.create');
        Route::post('/lessons', [AdminLessonController::class, 'store'])->name('lessons.store');
        Route::get('/lessons/{lesson}/edit', [AdminLessonController::class, 'edit'])->name('lessons.edit');
        Route::put('/lessons/{lesson}', [AdminLessonController::class, 'update'])->name('lessons.update');

        Route::post('/lessons/{lesson}/questions', [AdminQuestionController::class, 'store'])->name('questions.store');
        Route::delete('/questions/{question}', [AdminQuestionController::class, 'destroy'])->name('questions.destroy');
    });
});
