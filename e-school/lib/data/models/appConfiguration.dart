import 'package:eschool/data/models/chatSettings.dart';
import 'package:eschool/data/models/paymentOptions.dart';
import 'package:eschool/data/models/semester.dart';
import 'package:eschool/data/models/semesterBreak.dart';
import 'package:eschool/data/models/sessionYear.dart';

class AppConfiguration {
  AppConfiguration({
    required this.appLink,
    required this.iosAppLink,
    required this.appVersion,
    required this.iosAppVersion,
    required this.forceAppUpdate,
    required this.appMaintenance,
    required this.isCompulsoryFeePaymentMode,
    required this.isDemo,
    required this.canStudentPayTheirFees,
    required this.currentSemester,
    required this.paymentOptions,
    required this.semesterBreaks,
  });

  AppConfiguration.fromJson(Map<String, dynamic> json) {
    appLink = json['app_link'] ?? '';
    iosAppLink = json['ios_app_link'] ?? '';
    appVersion = json['app_version'] ?? '';
    iosAppVersion = json['ios_app_version'] ?? '';
    forceAppUpdate = json['force_app_update'] ?? '0';
    appMaintenance = json['app_maintenance'] ?? '0';
    schoolName = json['school_name'] ?? '';
    schoolTagline = json['school_tagline'] ?? '';
    sessionYear = SessionYear.fromJson(json['session_year'] ?? {});
    holidayDays =
        json['holiday_days']?.toString().toLowerCase().split(',') ?? [];
    onlineExamRules = json['online_exam_terms_condition'] ?? '';
    isOnlineFeesPaymentEnabled = json['online_payment'] ?? 'false';
    isDemo = json['is_demo'] ?? false;
    isCompulsoryFeePaymentMode = json['compulsory_fee_payment_mode'] == '1';
    canStudentPayTheirFees = json['is_student_can_pay_fees'] == '1';
    chatSettings = ChatSettings.fromJson(json['chat_settings'] ?? {});
    currentSemester = json['current_semester']?.isEmpty ?? true
        ? null
        : Semester.fromJson(json['current_semester'] ?? {});
    paymentOptions = json.containsKey('payment_options')
        ? PaymentOptions.fromJson(json['payment_options'])
        : paymentOptions;
    semesterBreaks = json['semester_breaks'] != null
        ? List<SemesterBreak>.from(
            (json['semester_breaks'] as List).map(
              (x) => SemesterBreak.fromJson(Map<String, dynamic>.from(x)),
            ),
          )
        : null;
  }
  late final bool isDemo;
  late final String appLink;
  late final String iosAppLink;
  late final String appVersion;
  late final String iosAppVersion;
  late final String forceAppUpdate;
  late final String appMaintenance;
  late final Semester? currentSemester;
  late final SessionYear sessionYear;
  late final List<String> holidayDays;
  late final String schoolName;
  late final String schoolTagline;
  late final ChatSettings chatSettings;
  late final String onlineExamRules;
  late final String isOnlineFeesPaymentEnabled;
  late final bool isCompulsoryFeePaymentMode;
  late final bool canStudentPayTheirFees;
  PaymentOptions paymentOptions = PaymentOptions();
  late final List<SemesterBreak>? semesterBreaks;
}
