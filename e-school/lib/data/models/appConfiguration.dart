import 'package:eschool_core/eschool_core.dart';
import 'package:eschool/data/models/term.dart';
import 'package:eschool/data/models/sessionYear.dart';

class AppConfiguration {
  AppConfiguration({
    required this.appLink,
    required this.iosAppLink,
    required this.appVersion,
    required this.iosAppVersion,
    required this.forceAppUpdate,
    required this.appMaintenance,
    required this.isDemo,
    required this.currentTerm,
    required this.termBreaks,
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
    isDemo = json['is_demo'] ?? false;
    chatSettings = ChatSettings.fromJson(json['chat_settings'] ?? {});
    currentTerm = json['current_term']?.isEmpty ?? true
        ? null
        : Term.fromJson(json['current_term'] ?? {});
    termBreaks = json['term_breaks'] != null
        ? List<TermBreak>.from(
            (json['term_breaks'] as List).map(
              (x) => TermBreak.fromJson(Map<String, dynamic>.from(x)),
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
  late final Term? currentTerm;
  late final SessionYear sessionYear;
  late final List<String> holidayDays;
  late final String schoolName;
  late final String schoolTagline;
  late final ChatSettings chatSettings;
  late final String onlineExamRules;
  late final List<TermBreak>? termBreaks;
}
