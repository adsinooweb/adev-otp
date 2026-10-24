<?php
/**
 * Build English source strings + fa_IR / ar PO/MO from a translation map.
 *
 * Usage (CLI inside a WordPress install):
 *   php wp-content/plugins/adev-otp-login/bin/build-i18n.php
 *
 * @package Adev_OTP
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	if ( 'cli' !== PHP_SAPI ) {
		exit;
	}

	$wp_load = dirname( __DIR__, 4 ) . '/wp-load.php';
	if ( ! is_readable( $wp_load ) ) {
		fwrite( STDERR, "WordPress bootstrap not found. Run from a WP install.\n" );
		exit( 1 );
	}
	require_once $wp_load;
}

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$root = dirname( __DIR__ );

/**
 * Persian (current msgid) => [ en, fa, ar ]
 *
 * @var array<string, array{0:string,1:string,2:string}>
 */
$map = array(
	'ورود پیامکی' => array( 'OTP Login', 'ورود پیامکی', 'تسجيل الدخول برمز SMS' ),
	'تنظیمات' => array( 'Settings', 'تنظیمات', 'الإعدادات' ),
	'لاگ‌ها' => array( 'Logs', 'لاگ‌ها', 'السجلات' ),
	'در حال ارسال…' => array( 'Sending…', 'در حال ارسال…', 'جارٍ الإرسال…' ),
	'ارسال موفق بود.' => array( 'Sent successfully.', 'ارسال موفق بود.', 'تم الإرسال بنجاح.' ),
	'خطا در ارسال.' => array( 'Sending failed.', 'خطا در ارسال.', 'فشل الإرسال.' ),
	'کپی شد' => array( 'Copied', 'کپی شد', 'تم النسخ' ),
	'عمومی' => array( 'General', 'عمومی', 'عام' ),
	'درگاه پیامک' => array( 'SMS Gateway', 'درگاه پیامک', 'بوابة الرسائل' ),
	'فرم‌ها' => array( 'Forms', 'فرم‌ها', 'النماذج' ),
	'شورتکدها' => array( 'Shortcodes', 'شورتکدها', 'الأكواد المختصرة' ),
	'ووکامرس' => array( 'WooCommerce', 'ووکامرس', 'ووكومرس' ),
	'امنیت' => array( 'Security', 'امنیت', 'الأمان' ),
	'دسترسی غیرمجاز.' => array( 'Unauthorized access.', 'دسترسی غیرمجاز.', 'وصول غير مصرح.' ),
	'شماره موبایل معتبر نیست.' => array( 'Invalid mobile number.', 'شماره موبایل معتبر نیست.', 'رقم الجوال غير صالح.' ),
	'درگاه فعال یافت نشد.' => array( 'No active gateway found.', 'درگاه فعال یافت نشد.', 'لم يتم العثور على بوابة نشطة.' ),
	'پیامک تست با %1$s به %2$s ارسال شد.' => array( 'Test SMS sent via %1$s to %2$s.', 'پیامک تست با %1$s به %2$s ارسال شد.', 'تم إرسال رسالة اختبار عبر %1$s إلى %2$s.' ),
	'ارسال ناموفق بود.' => array( 'Sending failed.', 'ارسال ناموفق بود.', 'فشل الإرسال.' ),
	'محدودیت‌های ارسال و کدهای موقت پاک شدند.' => array( 'Rate limits and temporary codes were cleared.', 'محدودیت‌های ارسال و کدهای موقت پاک شدند.', 'تم مسح حدود الإرسال والرموز المؤقتة.' ),
	'ورود پیامکی غیرفعال است.' => array( 'OTP login is disabled.', 'ورود پیامکی غیرفعال است.', 'تسجيل الدخول عبر الرسائل معطّل.' ),
	'درخواست نامعتبر است.' => array( 'Invalid request.', 'درخواست نامعتبر است.', 'الطلب غير صالح.' ),
	'اکشن نامعتبر.' => array( 'Invalid action.', 'اکشن نامعتبر.', 'إجراء غير صالح.' ),
	'حسابی با این شماره یافت نشد. ابتدا ثبت‌نام کنید.' => array( 'No account found with this number. Please register first.', 'حسابی با این شماره یافت نشد. ابتدا ثبت‌نام کنید.', 'لا يوجد حساب بهذا الرقم. سجّل أولًا.' ),
	'حسابی با این شماره یافت نشد.' => array( 'No account found with this number.', 'حسابی با این شماره یافت نشد.', 'لا يوجد حساب بهذا الرقم.' ),
	'شماره موبایل تأیید شد.' => array( 'Mobile number verified.', 'شماره موبایل تأیید شد.', 'تم تأكيد رقم الجوال.' ),
	'رمز عبور باید حداقل ۶ کاراکتر باشد.' => array( 'Password must be at least 6 characters.', 'رمز عبور باید حداقل ۶ کاراکتر باشد.', 'يجب أن تكون كلمة المرور ٦ أحرف على الأقل.' ),
	'رمز عبور با موفقیت تغییر کرد.' => array( 'Password changed successfully.', 'رمز عبور با موفقیت تغییر کرد.', 'تم تغيير كلمة المرور بنجاح.' ),
	'رمز عبور نادرست است.' => array( 'Incorrect password.', 'رمز عبور نادرست است.', 'كلمة المرور غير صحيحة.' ),
	'ورود موفق بود.' => array( 'Logged in successfully.', 'ورود موفق بود.', 'تم تسجيل الدخول بنجاح.' ),
	'امکان ارسال به این شماره وجود ندارد.' => array( 'This number cannot receive messages.', 'امکان ارسال به این شماره وجود ندارد.', 'لا يمكن الإرسال إلى هذا الرقم.' ),
	'درگاه پیامک فعال نیست.' => array( 'SMS gateway is not active.', 'درگاه پیامک فعال نیست.', 'بوابة الرسائل غير مفعّلة.' ),
	'ارسال پیامک ناموفق بود.' => array( 'SMS sending failed.', 'ارسال پیامک ناموفق بود.', 'فشل إرسال الرسالة.' ),
	'کد تأیید ارسال شد.' => array( 'Verification code sent.', 'کد تأیید ارسال شد.', 'تم إرسال رمز التأكيد.' ),
	'اطلاعات نامعتبر است.' => array( 'Invalid data.', 'اطلاعات نامعتبر است.', 'بيانات غير صالحة.' ),
	'کد منقضی شده است. دوباره درخواست دهید.' => array( 'Code expired. Please request a new one.', 'کد منقضی شده است. دوباره درخواست دهید.', 'انتهت صلاحية الرمز. اطلب رمزًا جديدًا.' ),
	'تعداد تلاش بیش از حد مجاز است.' => array( 'Too many attempts.', 'تعداد تلاش بیش از حد مجاز است.', 'تجاوزت عدد المحاولات المسموح.' ),
	'کد واردشده نادرست است.' => array( 'Incorrect code.', 'کد واردشده نادرست است.', 'الرمز المدخل غير صحيح.' ),
	'لطفاً %d ثانیه صبر کنید و دوباره تلاش کنید.' => array( 'Please wait %d seconds and try again.', 'لطفاً %d ثانیه صبر کنید و دوباره تلاش کنید.', 'يرجى الانتظار %d ثانية ثم المحاولة مجددًا.' ),
	'سقف ارسال روزانه برای این شماره پر شده است.' => array( 'Daily send limit for this number has been reached.', 'سقف ارسال روزانه برای این شماره پر شده است.', 'تم بلوغ حد الإرسال اليومي لهذا الرقم.' ),
	'سقف ارسال روزانه برای این IP پر شده است.' => array( 'Daily send limit for this IP has been reached.', 'سقف ارسال روزانه برای این IP پر شده است.', 'تم بلوغ حد الإرسال اليومي لهذا العنوان.' ),
	'نام الزامی است.' => array( 'Name is required.', 'نام الزامی است.', 'الاسم مطلوب.' ),
	'ایمیل معتبر وارد کنید.' => array( 'Please enter a valid email.', 'ایمیل معتبر وارد کنید.', 'يرجى إدخال بريد إلكتروني صالح.' ),
	'این ایمیل قبلاً ثبت شده است.' => array( 'This email is already registered.', 'این ایمیل قبلاً ثبت شده است.', 'هذا البريد مسجّل مسبقًا.' ),
	'ثبت‌نام با موفقیت انجام شد.' => array( 'Registration completed successfully.', 'ثبت‌نام با موفقیت انجام شد.', 'تم التسجيل بنجاح.' ),
	'در حال بررسی…' => array( 'Verifying…', 'در حال بررسی…', 'جارٍ التحقق…' ),
	'ارسال مجدد تا %d ثانیه' => array( 'Resend in %d seconds', 'ارسال مجدد تا %d ثانیه', 'إعادة الإرسال خلال %d ثانية' ),
	'ارسال مجدد کد' => array( 'Resend code', 'ارسال مجدد کد', 'إعادة إرسال الرمز' ),
	'خطایی رخ داد.' => array( 'An error occurred.', 'خطایی رخ داد.', 'حدث خطأ.' ),
	'شماره موبایل را وارد کنید.' => array( 'Please enter your mobile number.', 'شماره موبایل را وارد کنید.', 'أدخل رقم الجوال.' ),
	'کد تأیید را وارد کنید.' => array( 'Please enter the verification code.', 'کد تأیید را وارد کنید.', 'أدخل رمز التأكيد.' ),
	'ورود' => array( 'Login', 'ورود', 'دخول' ),
	'ثبت‌نام' => array( 'Register', 'ثبت‌نام', 'تسجيل' ),
	'ارسال کد' => array( 'Send code', 'ارسال کد', 'إرسال الرمز' ),
	'تأیید' => array( 'Verify', 'تأیید', 'تأكيد' ),
	'ورود / ثبت‌نام' => array( 'Login / Register', 'ورود / ثبت‌نام', 'دخول / تسجيل' ),
	'ورود با موبایل' => array( 'Login with mobile', 'ورود با موبایل', 'الدخول بالجوال' ),
	'فراموشی رمز' => array( 'Forgot password', 'فراموشی رمز', 'نسيت كلمة المرور' ),
	'کاوه‌نگار' => array( 'Kavenegar', 'کاوه‌نگار', 'كافه‌نگار' ),
	'API Key' => array( 'API Key', 'API Key', 'مفتاح API' ),
	'کلید API پنل کاوه‌نگار' => array( 'Kavenegar panel API key', 'کلید API پنل کاوه‌نگار', 'مفتاح API لوحة كافه‌نگار' ),
	'شماره فرستنده' => array( 'Sender number', 'شماره فرستنده', 'رقم المرسل' ),
	'برای ارسال ساده الزامی است.' => array( 'Required for simple send mode.', 'برای ارسال ساده الزامی است.', 'مطلوب لوضع الإرسال البسيط.' ),
	'حالت ارسال' => array( 'Send mode', 'حالت ارسال', 'وضع الإرسال' ),
	'Verify Lookup (الگو)' => array( 'Verify Lookup (template)', 'Verify Lookup (الگو)', 'Verify Lookup (قالب)' ),
	'ارسال ساده' => array( 'Simple send', 'ارسال ساده', 'إرسال بسيط' ),
	'نام الگوی Verify' => array( 'Verify template name', 'نام الگوی Verify', 'اسم قالب Verify' ),
	'نام قالب تعریف‌شده در پنل (token = کد OTP)' => array( 'Template name defined in the panel (token = OTP code)', 'نام قالب تعریف‌شده در پنل (token = کد OTP)', 'اسم القالب المعرّف في اللوحة (token = رمز OTP)' ),
	'متن پیام (ارسال ساده)' => array( 'Message text (simple send)', 'متن پیام (ارسال ساده)', 'نص الرسالة (إرسال بسيط)' ),
	'از {OTP} برای جایگزینی کد استفاده کنید.' => array( 'Use {OTP} as a placeholder for the code.', 'از {OTP} برای جایگزینی کد استفاده کنید.', 'استخدم {OTP} كعنصر نائب للرمز.' ),
	'API Key کاوه‌نگار تنظیم نشده است.' => array( 'Kavenegar API key is not configured.', 'API Key کاوه‌نگار تنظیم نشده است.', 'مفتاح API لكافه‌نگار غير مضبوط.' ),
	'نام الگوی Verify تنظیم نشده است.' => array( 'Verify template name is not configured.', 'نام الگوی Verify تنظیم نشده است.', 'اسم قالب Verify غير مضبوط.' ),
	'خطای کاوه‌نگار' => array( 'Kavenegar error', 'خطای کاوه‌نگار', 'خطأ كافه‌نگار' ),
	'ملی پیامک' => array( 'Melipayamak', 'ملی پیامک', 'ملي بيامك' ),
	'نام کاربری' => array( 'Username', 'نام کاربری', 'اسم المستخدم' ),
	'رمز عبور / API Key' => array( 'Password / API Key', 'رمز عبور / API Key', 'كلمة المرور / مفتاح API' ),
	'رمز پنل یا کلید API ملی پیامک (به‌صورت متن نمایش داده می‌شود)' => array( 'Melipayamak panel password or API key (shown as plain text)', 'رمز پنل یا کلید API ملی پیامک (به‌صورت متن نمایش داده می‌شود)', 'كلمة مرور اللوحة أو مفتاح API لملي بيامك (يُعرض كنص)' ),
	'ارسال الگویی' => array( 'Pattern send', 'ارسال الگویی', 'إرسال بنمط' ),
	'کد الگو (bodyId)' => array( 'Pattern code (bodyId)', 'کد الگو (bodyId)', 'رمز النمط (bodyId)' ),
	'شناسه الگوی تأییدشده در پنل ملی پیامک' => array( 'Approved pattern ID in the Melipayamak panel', 'شناسه الگوی تأییدشده در پنل ملی پیامک', 'معرّف النمط المعتمد في لوحة ملي بيامك' ),
	'نام کاربری یا رمز ملی پیامک تنظیم نشده است. در تب درگاه دوباره وارد و ذخیره کنید.' => array( 'Melipayamak username or password is not set. Enter and save them again in the Gateway tab.', 'نام کاربری یا رمز ملی پیامک تنظیم نشده است. در تب درگاه دوباره وارد و ذخیره کنید.', 'اسم المستخدم أو كلمة مرور ملي بيامك غير مضبوطين. أدخلهما واحفظهما في تبويب البوابة.' ),
	'کد الگو (bodyId) تنظیم نشده است.' => array( 'Pattern code (bodyId) is not configured.', 'کد الگو (bodyId) تنظیم نشده است.', 'رمز النمط (bodyId) غير مضبوط.' ),
	'شماره فرستنده برای ارسال ساده الزامی است.' => array( 'Sender number is required for simple send.', 'شماره فرستنده برای ارسال ساده الزامی است.', 'رقم المرسل مطلوب للإرسال البسيط.' ),
	'پاسخ نامعتبر ملی پیامک (HTTP %1$d): %2$s' => array( 'Invalid Melipayamak response (HTTP %1$d): %2$s', 'پاسخ نامعتبر ملی پیامک (HTTP %1$d): %2$s', 'استجابة ملي بيامك غير صالحة (HTTP %1$d): %2$s' ),
	'ارسال موفق' => array( 'Sent successfully', 'ارسال موفق', 'تم الإرسال بنجاح' ),
	'نام کاربری یا رمز اشتباه است.' => array( 'Incorrect username or password.', 'نام کاربری یا رمز اشتباه است.', 'اسم المستخدم أو كلمة المرور غير صحيحة.' ),
	'اعتبار پنل کافی نیست.' => array( 'Insufficient panel credit.', 'اعتبار پنل کافی نیست.', 'رصيد اللوحة غير كافٍ.' ),
	'پیام خالی است.' => array( 'Message is empty.', 'پیام خالی است.', 'الرسالة فارغة.' ),
	'زمان ارسال گذشته است.' => array( 'Send time has passed.', 'زمان ارسال گذشته است.', 'انتهى وقت الإرسال.' ),
	'پیامک حاوی کلمهٔ فیلترشده است.' => array( 'SMS contains a filtered word.', 'پیامک حاوی کلمهٔ فیلترشده است.', 'الرسالة تحتوي على كلمة محظورة.' ),
	'خط ارسال‌کننده معتبر نیست.' => array( 'Sender line is invalid.', 'خط ارسال‌کننده معتبر نیست.', 'خط المرسل غير صالح.' ),
	'کاربر غیرفعال است.' => array( 'User is inactive.', 'کاربر غیرفعال است.', 'المستخدم غير نشط.' ),
	'ارسال انجام نشد.' => array( 'Sending was not completed.', 'ارسال انجام نشد.', 'لم يتم الإرسال.' ),
	'مدارک پنل تکمیل نیست.' => array( 'Panel documents are incomplete.', 'مدارک پنل تکمیل نیست.', 'مستندات اللوحة غير مكتملة.' ),
	'شماره گیرنده صحیح نیست.' => array( 'Recipient number is invalid.', 'شماره گیرنده صحیح نیست.', 'رقم المستلم غير صحيح.' ),
	'خطای ملی پیامک' => array( 'Melipayamak error', 'خطای ملی پیامک', 'خطأ ملي بيامك' ),
	'شماره موبایل' => array( 'Mobile number', 'شماره موبایل', 'رقم الجوال' ),
	'ورود / ثبت‌نام برای ادامه تسویه حساب' => array( 'Login / Register to continue checkout', 'ورود / ثبت‌نام برای ادامه تسویه حساب', 'سجّل الدخول / سجّل للمتابعة إلى الدفع' ),
	'برای پرداخت سفارش، ابتدا با شماره موبایل وارد شوید یا ثبت‌نام کنید. پس از تأیید کد، به صفحه تسویه برمی‌گردید.' => array( 'To pay for your order, log in or register with your mobile number first. After verifying the code, you will return to checkout.', 'برای پرداخت سفارش، ابتدا با شماره موبایل وارد شوید یا ثبت‌نام کنید. پس از تأیید کد، به صفحه تسویه برمی‌گردید.', 'لدفع طلبك، سجّل الدخول أو أنشئ حسابًا برقم جوالك أولًا. بعد تأكيد الرمز ستعود إلى صفحة الدفع.' ),
	'تأیید موبایل با پیامک' => array( 'Verify mobile via SMS', 'تأیید موبایل با پیامک', 'تأكيد الجوال عبر الرسائل' ),
	'کد تأیید' => array( 'Verification code', 'کد تأیید', 'رمز التأكيد' ),
	'لطفاً شماره موبایل را با کد پیامکی تأیید کنید.' => array( 'Please verify your mobile number with the SMS code.', 'لطفاً شماره موبایل را با کد پیامکی تأیید کنید.', 'يرجى تأكيد رقم الجوال برمز الرسالة النصية.' ),
	'تنظیمات ورود پیامکی' => array( 'OTP Login Settings', 'تنظیمات ورود پیامکی', 'إعدادات تسجيل الدخول برمز SMS' ),
	'فعال‌سازی' => array( 'Enable', 'فعال‌سازی', 'تفعيل' ),
	'افزونه فعال باشد' => array( 'Plugin is enabled', 'افزونه فعال باشد', 'الإضافة مفعّلة' ),
	'جایگزینی wp-login' => array( 'Replace wp-login', 'جایگزینی wp-login', 'استبدال wp-login' ),
	'صفحه ورود وردپرس با فرم OTP جایگزین شود' => array( 'Replace the WordPress login page with the OTP form', 'صفحه ورود وردپرس با فرم OTP جایگزین شود', 'استبدال صفحة دخول ووردبريس بنموذج OTP' ),
	'طول کد OTP' => array( 'OTP length', 'طول کد OTP', 'طول رمز OTP' ),
	'اعتبار کد (ثانیه)' => array( 'Code TTL (seconds)', 'اعتبار کد (ثانیه)', 'صلاحية الرمز (ثوانٍ)' ),
	'حالت ورود' => array( 'Login mode', 'حالت ورود', 'وضع تسجيل الدخول' ),
	'فقط OTP' => array( 'OTP only', 'فقط OTP', 'OTP فقط' ),
	'OTP + رمز عبور' => array( 'OTP + password', 'OTP + رمز عبور', 'OTP + كلمة المرور' ),
	'درگاه فعال' => array( 'Active gateway', 'درگاه فعال', 'البوابة النشطة' ),
	'تست ارسال' => array( 'Send test', 'تست ارسال', 'اختبار الإرسال' ),
	'ارسال پیامک تست' => array( 'Send test SMS', 'ارسال پیامک تست', 'إرسال رسالة اختبار' ),
	'پاک کردن محدودیت ارسال' => array( 'Clear send limits', 'پاک کردن محدودیت ارسال', 'مسح حدود الإرسال' ),
	'ابتدا تنظیمات را ذخیره کنید، سپس تست بگیرید. اگر سقف روزانه پر شده، دکمه پاک کردن محدودیت را بزنید.' => array( 'Save settings first, then run a test. If the daily limit is reached, click Clear send limits.', 'ابتدا تنظیمات را ذخیره کنید، سپس تست بگیرید. اگر سقف روزانه پر شده، دکمه پاک کردن محدودیت را بزنید.', 'احفظ الإعدادات أولًا ثم اختبر. إذا بلغ الحد اليومي، اضغط مسح حدود الإرسال.' ),
	'نام در ثبت‌نام' => array( 'Name on registration', 'نام در ثبت‌نام', 'الاسم عند التسجيل' ),
	'الزامی' => array( 'Required', 'الزامی', 'مطلوب' ),
	'ایمیل در ثبت‌نام' => array( 'Email on registration', 'ایمیل در ثبت‌نام', 'البريد عند التسجيل' ),
	'متن دکمه ورود' => array( 'Login button text', 'متن دکمه ورود', 'نص زر الدخول' ),
	'متن دکمه ثبت‌نام' => array( 'Register button text', 'متن دکمه ثبت‌نام', 'نص زر التسجيل' ),
	'متن ارسال کد' => array( 'Send code button text', 'متن ارسال کد', 'نص زر إرسال الرمز' ),
	'متن تأیید' => array( 'Verify button text', 'متن تأیید', 'نص زر التأكيد' ),
	'رنگ پس‌زمینه دکمه' => array( 'Button background color', 'رنگ پس‌زمینه دکمه', 'لون خلفية الزر' ),
	'رنگ متن دکمه' => array( 'Button text color', 'رنگ متن دکمه', 'لون نص الزر' ),
	'ریدایرکت بعد از ورود' => array( 'Redirect after login', 'ریدایرکت بعد از ورود', 'إعادة التوجيه بعد الدخول' ),
	'ریدایرکت بعد از ثبت‌نام' => array( 'Redirect after registration', 'ریدایرکت بعد از ثبت‌نام', 'إعادة التوجيه بعد التسجيل' ),
	'فرم ورود با کد یکبارمصرف پیامکی' => array( 'Login form with one-time SMS code', 'فرم ورود با کد یکبارمصرف پیامکی', 'نموذج الدخول برمز SMS لمرة واحدة' ),
	'فرم ثبت‌نام با تأیید شماره موبایل' => array( 'Registration form with mobile verification', 'فرم ثبت‌نام با تأیید شماره موبایل', 'نموذج التسجيل مع تأكيد الجوال' ),
	'فرم فراموشی / تغییر رمز عبور با OTP' => array( 'Forgot / reset password form with OTP', 'فرم فراموشی / تغییر رمز عبور با OTP', 'نموذج نسيت / تغيير كلمة المرور عبر OTP' ),
	'دکمهٔ باز کردن مودال ورود / ثبت‌نام / فراموشی رمز' => array( 'Button that opens the login / register / forgot password modal', 'دکمهٔ باز کردن مودال ورود / ثبت‌نام / فراموشی رمز', 'زر يفتح نافذة الدخول / التسجيل / نسيت كلمة المرور' ),
	'همان مودال با متن دلخواه برای دکمه' => array( 'Same modal with custom button text', 'همان مودال با متن دلخواه برای دکمه', 'نفس النافذة مع نص زر مخصص' ),
	'فرم تب‌دار ورود / ثبت‌نام / فراموشی (بدون پاپ‌آپ)' => array( 'Tabbed login / register / forgot form (no popup)', 'فرم تب‌دار ورود / ثبت‌نام / فراموشی (بدون پاپ‌آپ)', 'نموذج بتبويبات للدخول / التسجيل / الاستعادة (بدون نافذة)' ),
	'شورتکد موردنظر را کپی کرده و در برگه، نوشته یا ویجت قرار دهید.' => array( 'Copy the shortcode and place it in a page, post, or widget.', 'شورتکد موردنظر را کپی کرده و در برگه، نوشته یا ویجت قرار دهید.', 'انسخ الكود المختصر وضعه في صفحة أو مقال أو ودجت.' ),
	'شورتکد' => array( 'Shortcode', 'شورتکد', 'كود مختصر' ),
	'توضیح' => array( 'Description', 'توضیح', 'الوصف' ),
	'عملیات' => array( 'Action', 'عملیات', 'إجراء' ),
	'کپی' => array( 'Copy', 'کپی', 'نسخ' ),
	'تأیید موبایل در تسویه' => array( 'Verify mobile at checkout', 'تأیید موبایل در تسویه', 'تأكيد الجوال عند الدفع' ),
	'نمایش تأیید OTP در صفحه checkout' => array( 'Show OTP verification on the checkout page', 'نمایش تأیید OTP در صفحه checkout', 'إظهار تأكيد OTP في صفحة الدفع' ),
	'اجباری بودن تأیید' => array( 'Require verification', 'اجباری بودن تأیید', 'إلزام التأكيد' ),
	'بدون تأیید OTP امکان ثبت سفارش نباشد' => array( 'Do not allow placing an order without OTP verification', 'بدون تأیید OTP امکان ثبت سفارش نباشد', 'عدم السماح بإتمام الطلب دون تأكيد OTP' ),
	'ورود / ثبت‌نام اجباری در تسویه حساب' => array( 'Force login / register at checkout', 'ورود / ثبت‌نام اجباری در تسویه حساب', 'إلزام الدخول / التسجيل عند الدفع' ),
	'کاربر مهمان قبل از پرداخت باید با OTP وارد شود یا ثبت‌نام کند' => array( 'Guests must log in or register with OTP before paying', 'کاربر مهمان قبل از پرداخت باید با OTP وارد شود یا ثبت‌نام کند', 'يجب على الزائر تسجيل الدخول أو التسجيل عبر OTP قبل الدفع' ),
	'در صفحه checkout اگر کاربر لاگین نباشد، فرم ورود/ثبت‌نام نمایش داده می‌شود و پس از تأیید کد، صفحه رفرش می‌شود.' => array( 'On checkout, if the user is not logged in, the login/register form is shown and the page refreshes after code verification.', 'در صفحه checkout اگر کاربر لاگین نباشد، فرم ورود/ثبت‌نام نمایش داده می‌شود و پس از تأیید کد، صفحه رفرش می‌شود.', 'في صفحة الدفع، إذا لم يكن المستخدم مسجّلًا، يظهر نموذج الدخول/التسجيل وتُحدَّث الصفحة بعد تأكيد الرمز.' ),
	'حداکثر تلاش کد' => array( 'Max code attempts', 'حداکثر تلاش کد', 'أقصى محاولات للرمز' ),
	'فاصله ارسال مجدد (ثانیه)' => array( 'Resend cooldown (seconds)', 'فاصله ارسال مجدد (ثانیه)', 'مهلة إعادة الإرسال (ثوانٍ)' ),
	'سقف روزانه هر شماره' => array( 'Daily limit per number', 'سقف روزانه هر شماره', 'الحد اليومي لكل رقم' ),
	'سقف روزانه هر IP' => array( 'Daily limit per IP', 'سقف روزانه هر IP', 'الحد اليومي لكل IP' ),
	'لیست سیاه شماره‌ها' => array( 'Phone blacklist', 'لیست سیاه شماره‌ها', 'القائمة السوداء للأرقام' ),
	'شماره‌ها را با کاما یا خط جدید جدا کنید.' => array( 'Separate numbers with commas or new lines.', 'شماره‌ها را با کاما یا خط جدید جدا کنید.', 'افصل الأرقام بفاصلة أو سطر جديد.' ),
	'Honeypot' => array( 'Honeypot', 'Honeypot', 'Honeypot' ),
	'فیلد مخفی ضد ربات' => array( 'Hidden anti-bot field', 'فیلد مخفی ضد ربات', 'حقل مخفي ضد الروبوتات' ),
	'ذخیره تنظیمات' => array( 'Save settings', 'ذخیره تنظیمات', 'حفظ الإعدادات' ),
	'رمز عبور جدید' => array( 'New password', 'رمز عبور جدید', 'كلمة المرور الجديدة' ),
	'رمز عبور' => array( 'Password', 'رمز عبور', 'كلمة المرور' ),
	'نام' => array( 'Name', 'نام', 'الاسم' ),
	'ایمیل' => array( 'Email', 'ایمیل', 'البريد الإلكتروني' ),
	'ورود و ثبت‌نام' => array( 'Login and register', 'ورود و ثبت‌نام', 'الدخول والتسجيل' ),
	'بستن' => array( 'Close', 'بستن', 'إغلاق' ),
	'شماره موبایل خود را وارد کنید تا کد تأیید برایتان پیامک شود.' => array( 'Enter your mobile number to receive a verification SMS.', 'شماره موبایل خود را وارد کنید تا کد تأیید برایتان پیامک شود.', 'أدخل رقم جوالك لاستلام رمز التأكيد عبر الرسائل.' ),
	'برای ساخت حساب جدید، شماره موبایل خود را وارد کنید.' => array( 'Enter your mobile number to create a new account.', 'برای ساخت حساب جدید، شماره موبایل خود را وارد کنید.', 'أدخل رقم جوالك لإنشاء حساب جديد.' ),
	'لاگ‌های ارسال پیامک' => array( 'SMS send logs', 'لاگ‌های ارسال پیامک', 'سجلات إرسال الرسائل' ),
	'کد OTP ذخیره نمی‌شود. شماره موبایل به‌صورت ماسک‌شده نمایش داده می‌شود.' => array( 'OTP codes are not stored. Mobile numbers are shown masked.', 'کد OTP ذخیره نمی‌شود. شماره موبایل به‌صورت ماسک‌شده نمایش داده می‌شود.', 'لا يتم تخزين رموز OTP. تُعرض أرقام الجوال مقنّعة.' ),
	'زمان' => array( 'Time', 'زمان', 'الوقت' ),
	'موبایل' => array( 'Mobile', 'موبایل', 'الجوال' ),
	'درگاه' => array( 'Gateway', 'درگاه', 'البوابة' ),
	'وضعیت' => array( 'Status', 'وضعیت', 'الحالة' ),
	'پیام' => array( 'Message', 'پیام', 'الرسالة' ),
	'لاگی ثبت نشده است.' => array( 'No logs yet.', 'لاگی ثبت نشده است.', 'لا توجد سجلات بعد.' ),
	'موفق' => array( 'Success', 'موفق', 'نجاح' ),
	'ناموفق' => array( 'Failed', 'ناموفق', 'فشل' ),
	'کد تأیید شما: {OTP}' => array( 'Your verification code: {OTP}', 'کد تأیید شما: {OTP}', 'رمز التأكيد الخاص بك: {OTP}' ),
	'Done.' => array( 'Done.', 'انجام شد.', 'تم.' ),
	'Copy shortcode:' => array( 'Copy shortcode:', 'کپی شورتکد:', 'انسخ الكود المختصر:' ),
	'Polylang' => array( 'Polylang', 'Polylang', 'Polylang' ),
	'Button labels are registered for Polylang String Translations (Languages → String translations → Adev OTP Login). Built-in UI strings follow the current language (fa_IR, en_US, ar).' => array(
		'Button labels are registered for Polylang String Translations (Languages → String translations → Adev OTP Login). Built-in UI strings follow the current language (fa_IR, en_US, ar).',
		'متن دکمه‌ها در ترجمه‌های رشتهٔ Polylang ثبت می‌شود (زبان‌ها ← ترجمه‌های رشته ← Adev OTP Login). رشته‌های رابط کاربری مطابق زبان جاری (fa_IR، en_US، ar) نمایش داده می‌شوند.',
		'تُسجَّل نصوص الأزرار في ترجمات سلاسل Polylang (اللغات ← ترجمات السلاسل ← Adev OTP Login). تتبع نصوص الواجهة اللغة الحالية (fa_IR و en_US و ar).',
	),
);

// Sort longest first so replacements do not corrupt shorter fragments inside longer strings.
$keys = array_keys( $map );
usort(
	$keys,
	static function ( $a, $b ) {
		return mb_strlen( $b ) <=> mb_strlen( $a );
	}
);

$iterator = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS )
);

$php_files = array();
foreach ( $iterator as $file ) {
	/** @var SplFileInfo $file */
	$path = $file->getPathname();
	if ( 'php' !== $file->getExtension() ) {
		continue;
	}
	if ( false !== strpos( $path, DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR ) ) {
		continue;
	}
	// Keep intentional Persian literals (e.g. legacy migration maps).
	if ( false !== strpos( $path, DIRECTORY_SEPARATOR . 'I18n' . DIRECTORY_SEPARATOR ) ) {
		continue;
	}
	$php_files[] = $path;
}

$replaced_files = 0;
foreach ( $php_files as $path ) {
	$contents = file_get_contents( $path );
	$original = $contents;
	foreach ( $keys as $fa ) {
		$en       = $map[ $fa ][0];
		$contents = str_replace( $fa, $en, $contents );
	}
	if ( $contents !== $original ) {
		file_put_contents( $path, $contents );
		++$replaced_files;
		$relative = str_replace( $root . DIRECTORY_SEPARATOR, '', $path );
		echo esc_html( 'Updated: ' . $relative ) . "\n";
	}
}

echo esc_html( sprintf( 'PHP files updated: %d', $replaced_files ) ) . "\n";

// Build unique EN strings for POT / PO.
$en_to_fa = array();
$en_to_ar = array();
foreach ( $map as $fa => $trio ) {
	$en                = $trio[0];
	$en_to_fa[ $en ]   = $trio[1];
	$en_to_ar[ $en ]   = $trio[2];
}

$lang_dir = $root . '/languages';
if ( ! function_exists( 'WP_Filesystem' ) ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
}
global $wp_filesystem;
if ( ! WP_Filesystem() ) {
	fwrite( STDERR, "Could not initialize WP_Filesystem.\n" );
	exit( 1 );
}
if ( ! $wp_filesystem->is_dir( $lang_dir ) ) {
	$wp_filesystem->mkdir( $lang_dir );
}

$pot_header = <<<'HDR'
# Copyright (C) 2026 Adev
# This file is distributed under the GPL-2.0-or-later.
msgid ""
msgstr ""
"Project-Id-Version: Adev OTP Login 1.1.0\n"
"Report-Msgid-Bugs-To: https://adev.ir\n"
"POT-Creation-Date: 2026-10-10 15:00+0330\n"
"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\n"
"Last-Translator: FULL NAME <EMAIL@ADDRESS>\n"
"Language-Team: LANGUAGE <LL@li.org>\n"
"Language: \n"
"MIME-Version: 1.0\n"
"Content-Type: text/plain; charset=UTF-8\n"
"Content-Transfer-Encoding: 8bit\n"
"X-Domain: adev-otp-login\n"

HDR;

function write_po( string $path, string $header, array $translations ): void {
	$out = $header;
	ksort( $translations );
	foreach ( $translations as $msgid => $msgstr ) {
		$out .= "\n";
		$out .= 'msgid "' . po_escape( $msgid ) . "\"\n";
		$out .= 'msgstr "' . po_escape( $msgstr ) . "\"\n";
	}
	file_put_contents( $path, $out );
}

function po_escape( string $s ): string {
	return str_replace(
		array( '\\', '"', "\n", "\r", "\t" ),
		array( '\\\\', '\\"', '\\n', '', '\\t' ),
		$s
	);
}

// POT (empty msgstr).
$pot_map = array();
foreach ( array_keys( $en_to_fa ) as $en ) {
	$pot_map[ $en ] = '';
}
write_po( $lang_dir . '/adev-otp-login.pot', $pot_header, $pot_map );

$fa_header = <<<'HDR'
# Copyright (C) 2026 Adev
# This file is distributed under the GPL-2.0-or-later.
msgid ""
msgstr ""
"Project-Id-Version: Adev OTP Login 1.1.0\n"
"Report-Msgid-Bugs-To: https://adev.ir\n"
"POT-Creation-Date: 2026-10-10 15:00+0330\n"
"PO-Revision-Date: 2026-10-10 15:00+0330\n"
"Last-Translator: Adev\n"
"Language-Team: Persian\n"
"Language: fa_IR\n"
"MIME-Version: 1.0\n"
"Content-Type: text/plain; charset=UTF-8\n"
"Content-Transfer-Encoding: 8bit\n"
"Plural-Forms: nplurals=2; plural=(n > 1);\n"
"X-Domain: adev-otp-login\n"

HDR;

$ar_header = <<<'HDR'
# Copyright (C) 2026 Adev
# This file is distributed under the GPL-2.0-or-later.
msgid ""
msgstr ""
"Project-Id-Version: Adev OTP Login 1.1.0\n"
"Report-Msgid-Bugs-To: https://adev.ir\n"
"POT-Creation-Date: 2026-10-10 15:00+0330\n"
"PO-Revision-Date: 2026-10-10 15:00+0330\n"
"Last-Translator: Adev\n"
"Language-Team: Arabic\n"
"Language: ar\n"
"MIME-Version: 1.0\n"
"Content-Type: text/plain; charset=UTF-8\n"
"Content-Transfer-Encoding: 8bit\n"
"Plural-Forms: nplurals=6; plural=(n==0 ? 0 : n==1 ? 1 : n==2 ? 2 : n%100>=3 && n%100<=10 ? 3 : n%100>=11 ? 4 : 5);\n"
"X-Domain: adev-otp-login\n"

HDR;

$en_header = <<<'HDR'
# Copyright (C) 2026 Adev
# This file is distributed under the GPL-2.0-or-later.
msgid ""
msgstr ""
"Project-Id-Version: Adev OTP Login 1.1.0\n"
"Report-Msgid-Bugs-To: https://adev.ir\n"
"POT-Creation-Date: 2026-10-10 15:00+0330\n"
"PO-Revision-Date: 2026-10-10 15:00+0330\n"
"Last-Translator: Adev\n"
"Language-Team: English\n"
"Language: en_US\n"
"MIME-Version: 1.0\n"
"Content-Type: text/plain; charset=UTF-8\n"
"Content-Transfer-Encoding: 8bit\n"
"Plural-Forms: nplurals=2; plural=(n != 1);\n"
"X-Domain: adev-otp-login\n"

HDR;

write_po( $lang_dir . '/adev-otp-login-fa_IR.po', $fa_header, $en_to_fa );
write_po( $lang_dir . '/adev-otp-login-ar.po', $ar_header, $en_to_ar );

$en_identity = array();
foreach ( array_keys( $en_to_fa ) as $en ) {
	$en_identity[ $en ] = $en;
}
write_po( $lang_dir . '/adev-otp-login-en_US.po', $en_header, $en_identity );

echo esc_html(
	sprintf(
		'Wrote POT + fa_IR + ar + en_US PO files (%d strings)',
		count( $en_to_fa )
	)
) . "\n";

/**
 * Minimal MO writer (no plurals).
 *
 * @param array<string,string> $translations msgid => msgstr
 */
function write_mo( string $path, array $translations ): void {
	ksort( $translations );
	$ids  = '';
	$strs = '';
	$offsets = array();
	foreach ( $translations as $id => $str ) {
		$offsets[] = array( strlen( $ids ), strlen( $id ), strlen( $strs ), strlen( $str ) );
		$ids      .= $id . "\0";
		$strs     .= $str . "\0";
	}
	$count  = count( $translations );
	$keystart = 28;
	$valuestart = $keystart + ( $count * 8 );
	$idstart = $valuestart + ( $count * 8 );
	$strstart = $idstart + strlen( $ids );

	$mo = pack( 'V*', 0x950412de, 0, $count, $keystart, $valuestart, 0, $keystart );
	foreach ( $offsets as $o ) {
		$mo .= pack( 'VV', $o[1], $idstart + $o[0] );
	}
	foreach ( $offsets as $o ) {
		$mo .= pack( 'VV', $o[3], $strstart + $o[2] );
	}
	$mo .= $ids . $strs;
	file_put_contents( $path, $mo );
}

write_mo( $lang_dir . '/adev-otp-login-fa_IR.mo', $en_to_fa );
write_mo( $lang_dir . '/adev-otp-login-ar.mo', $en_to_ar );
write_mo( $lang_dir . '/adev-otp-login-en_US.mo', $en_identity );

echo esc_html( 'Compiled MO files.' ) . "\n";
echo esc_html( 'Done.' ) . "\n";
