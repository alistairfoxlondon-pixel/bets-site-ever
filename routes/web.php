<?php

use App\Http\Controllers\Admin\AffiliateController as AdminAffiliateController;
use App\Http\Controllers\Admin\AuditController as AdminAuditController;
use App\Http\Controllers\Admin\BlogPostController as AdminBlogPostController;
use App\Http\Controllers\Admin\ChatController as AdminChatController;
use App\Http\Controllers\Admin\ContactMessageController as AdminContactMessageController;
use App\Http\Controllers\Admin\CouponController as AdminCouponController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\GatewayController as AdminGatewayController;
use App\Http\Controllers\Admin\InvoiceController as AdminInvoiceController;
use App\Http\Controllers\Admin\LogController as AdminLogController;
use App\Http\Controllers\Admin\PageController as AdminPageController;
use App\Http\Controllers\Admin\PlanController as AdminPlanController;
use App\Http\Controllers\Admin\PromptController as AdminPromptController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AffiliateController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\IndexingController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SupportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public site
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/pricing', [PricingController::class, 'index'])->name('pricing');
Route::get('/features', [PricingController::class, 'features'])->name('features');

Route::get('/tools/adsense-readiness', [AuditController::class, 'landing'])->name('audit.landing');
Route::get('/tools/content-writer', [DocumentController::class, 'landing'])->name('writer.landing');
Route::get('/tools/affiliate-content-writer', [DocumentController::class, 'affiliateLanding'])->name('writer.affiliate');
Route::get('/tools/indexing', [IndexingController::class, 'landing'])->name('indexing.landing');
Route::get('/tools/affiliate-program', [AffiliateController::class, 'landing'])->name('affiliate.landing');

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

Route::get('/contact', [ContactController::class, 'create'])->name('contact.create');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

Route::get('/page/{slug}', [PageController::class, 'show'])->name('page.show');

Route::get('/ref/{code}', [ReferralController::class, 'capture'])->name('referral.capture');

Route::post('/billing/webhook/{gateway}', [BillingController::class, 'webhook'])->name('billing.webhook');

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');

    Route::get('/forgot-password', [PasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'resetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');

    Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('google.redirect');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/two-factor-challenge', [TwoFactorChallengeController::class, 'create'])->name('two-factor.challenge');
    Route::post('/two-factor-challenge', [TwoFactorChallengeController::class, 'store'])->name('two-factor.verify');
    Route::match(['get', 'post'], '/two-factor-recovery', [TwoFactorChallengeController::class, 'recovery'])->name('two-factor.recovery');

    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
});

/*
|--------------------------------------------------------------------------
| Product
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'two-factor'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/audits', [AuditController::class, 'index'])->name('audits.index');
    Route::get('/audits/new', [AuditController::class, 'create'])->name('audits.create');
    Route::post('/audits', [AuditController::class, 'store'])->name('audits.store');
    Route::get('/audits/compare', [AuditController::class, 'compare'])->name('audits.compare');
    Route::get('/audits/{audit}', [AuditController::class, 'show'])->name('audits.show');
    Route::get('/audits/{audit}/report.pdf', [AuditController::class, 'report'])->name('audits.report');
    Route::get('/audits/{audit}/preview', [AuditController::class, 'preview'])->name('audits.preview');
    Route::post('/audits/{audit}/rerun', [AuditController::class, 'rerun'])->name('audits.rerun');
    Route::delete('/audits/{audit}', [AuditController::class, 'destroy'])->name('audits.destroy');

    Route::get('/content', [DocumentController::class, 'index'])->name('documents.index');
    Route::get('/content/new', [DocumentController::class, 'create'])->name('documents.create');
    Route::post('/content', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('/content/{document}', [DocumentController::class, 'show'])->name('documents.show');
    Route::get('/content/{document}/export/{format}', [DocumentController::class, 'export'])->name('documents.export');
    Route::delete('/content/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');

    Route::get('/indexing', [IndexingController::class, 'index'])->name('indexing.index');
    Route::post('/indexing', [IndexingController::class, 'store'])->name('indexing.store');
    Route::get('/indexing/{key}.txt', [IndexingController::class, 'keyFile'])->name('indexing.key');

    Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
    Route::get('/billing/invoices', [BillingController::class, 'invoices'])->name('billing.invoices');
    Route::get('/billing/invoices/{invoice}', [BillingController::class, 'invoice'])->name('billing.invoice');
    Route::post('/billing/subscribe/{plan}/{cycle}', [BillingController::class, 'subscribe'])->name('billing.subscribe');
    Route::get('/billing/checkout/{invoice}', [BillingController::class, 'checkout'])->name('billing.checkout');
    Route::post('/billing/checkout/{invoice}', [BillingController::class, 'process'])->name('billing.process');
    Route::get('/billing/callback/{gateway}/{invoice}', [BillingController::class, 'callback'])->name('billing.callback');
    Route::post('/billing/subscription/{subscription}/cancel', [BillingController::class, 'cancel'])->name('billing.cancel');
    Route::post('/billing/subscription/{subscription}/resume', [BillingController::class, 'resume'])->name('billing.resume');
    Route::post('/billing/invoice/{invoice}/manual', [BillingController::class, 'confirmManual'])->name('billing.manual');

    Route::get('/referrals', [AffiliateController::class, 'index'])->name('affiliate.index');
    Route::post('/referrals/payout', [AffiliateController::class, 'payout'])->name('affiliate.payout');

    Route::get('/settings', [SettingsController::class, 'profile'])->name('settings.profile');
    Route::post('/settings', [SettingsController::class, 'updateProfile'])->name('settings.profile.update');
    Route::get('/settings/security', [SettingsController::class, 'security'])->name('settings.security');
    Route::post('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');
    Route::post('/settings/email', [SettingsController::class, 'updateEmail'])->name('settings.email');
    Route::delete('/settings/account', [SettingsController::class, 'destroy'])->name('settings.destroy');
    Route::get('/settings/two-factor', [SettingsController::class, 'twoFactor'])->name('settings.two-factor');
    Route::post('/settings/two-factor/enable', [SettingsController::class, 'enableTwoFactor'])->name('settings.two-factor.enable');
    Route::post('/settings/two-factor/confirm', [SettingsController::class, 'confirmTwoFactor'])->name('settings.two-factor.confirm');
    Route::post('/settings/two-factor/disable', [SettingsController::class, 'disableTwoFactor'])->name('settings.two-factor.disable');
    Route::post('/settings/two-factor/recovery-codes', [SettingsController::class, 'regenerateRecoveryCodes'])->name('settings.two-factor.recovery');

    Route::get('/support', [SupportController::class, 'index'])->name('support.index');
    Route::post('/support', [SupportController::class, 'store'])->name('support.store');
    Route::get('/support/messages', [SupportController::class, 'messages'])->name('support.messages');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
});

/*
|--------------------------------------------------------------------------
| Administration
|--------------------------------------------------------------------------
*/

Route::prefix('manage')->name('admin.')->middleware(['auth', 'two-factor', 'admin'])->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
    Route::post('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::post('/users/{user}/suspend', [AdminUserController::class, 'suspend'])->name('users.suspend');
    Route::post('/users/{user}/credits', [AdminUserController::class, 'credits'])->name('users.credits');
    Route::post('/users/{user}/password', [AdminUserController::class, 'password'])->name('users.password');

    Route::get('/plans', [AdminPlanController::class, 'index'])->name('plans.index');
    Route::post('/plans', [AdminPlanController::class, 'store'])->name('plans.store');
    Route::post('/plans/{plan}', [AdminPlanController::class, 'update'])->name('plans.update');
    Route::delete('/plans/{plan}', [AdminPlanController::class, 'destroy'])->name('plans.destroy');

    Route::get('/gateways', [AdminGatewayController::class, 'index'])->name('gateways.index');
    Route::post('/gateways/{gateway}', [AdminGatewayController::class, 'update'])->name('gateways.update');
    Route::post('/gateways/{gateway}/toggle', [AdminGatewayController::class, 'toggle'])->name('gateways.toggle');

    Route::get('/invoices', [AdminInvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/{invoice}', [AdminInvoiceController::class, 'show'])->name('invoices.show');
    Route::post('/invoices/{invoice}/mark-paid', [AdminInvoiceController::class, 'markPaid'])->name('invoices.mark-paid');

    Route::get('/coupons', [AdminCouponController::class, 'index'])->name('coupons.index');
    Route::post('/coupons', [AdminCouponController::class, 'store'])->name('coupons.store');
    Route::post('/coupons/{coupon}', [AdminCouponController::class, 'update'])->name('coupons.update');
    Route::delete('/coupons/{coupon}', [AdminCouponController::class, 'destroy'])->name('coupons.destroy');

    Route::get('/audits', [AdminAuditController::class, 'index'])->name('audits.index');
    Route::get('/audits/{audit}', [AdminAuditController::class, 'show'])->name('audits.show');

    Route::get('/blog', [AdminBlogPostController::class, 'index'])->name('blog.index');
    Route::get('/blog/create', [AdminBlogPostController::class, 'create'])->name('blog.create');
    Route::post('/blog', [AdminBlogPostController::class, 'store'])->name('blog.store');
    Route::get('/blog/{post}/edit', [AdminBlogPostController::class, 'edit'])->name('blog.edit');
    Route::put('/blog/{post}', [AdminBlogPostController::class, 'update'])->name('blog.update');
    Route::delete('/blog/{post}', [AdminBlogPostController::class, 'destroy'])->name('blog.destroy');

    Route::get('/pages', [AdminPageController::class, 'index'])->name('pages.index');
    Route::get('/pages/create', [AdminPageController::class, 'create'])->name('pages.create');
    Route::post('/pages', [AdminPageController::class, 'store'])->name('pages.store');
    Route::get('/pages/{page}/edit', [AdminPageController::class, 'edit'])->name('pages.edit');
    Route::put('/pages/{page}', [AdminPageController::class, 'update'])->name('pages.update');
    Route::delete('/pages/{page}', [AdminPageController::class, 'destroy'])->name('pages.destroy');

    Route::get('/messages', [AdminContactMessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/{message}', [AdminContactMessageController::class, 'show'])->name('messages.show');
    Route::post('/messages/{message}/reply', [AdminContactMessageController::class, 'reply'])->name('messages.reply');
    Route::delete('/messages/{message}', [AdminContactMessageController::class, 'destroy'])->name('messages.destroy');

    Route::get('/chat', [AdminChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/{conversation}', [AdminChatController::class, 'show'])->name('chat.show');
    Route::post('/chat/{conversation}/reply', [AdminChatController::class, 'reply'])->name('chat.reply');

    Route::get('/affiliate', [AdminAffiliateController::class, 'index'])->name('affiliate.index');
    Route::post('/affiliate/settings', [AdminAffiliateController::class, 'settings'])->name('affiliate.settings');
    Route::get('/affiliate/payouts/{payout}', [AdminAffiliateController::class, 'payout'])->name('affiliate.payout');
    Route::post('/affiliate/payouts/{payout}', [AdminAffiliateController::class, 'updatePayout'])->name('affiliate.payout.update');

    Route::get('/settings', [AdminSettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [AdminSettingController::class, 'update'])->name('settings.update');
    Route::post('/settings/secret', [AdminSettingController::class, 'updateSecret'])->name('settings.secret');

    Route::get('/prompts', [AdminPromptController::class, 'index'])->name('prompts.index');
    Route::post('/prompts', [AdminPromptController::class, 'update'])->name('prompts.update');
    Route::post('/prompts/reset', [AdminPromptController::class, 'reset'])->name('prompts.reset');

    Route::get('/logs', [AdminLogController::class, 'index'])->name('logs.index');
});
