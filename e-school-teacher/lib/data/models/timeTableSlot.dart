import 'package:eschool_teacher/data/models/classSectionDetails.dart';
import 'package:eschool_teacher/data/models/subject.dart';

class TimeTableSlot {
  TimeTableSlot({
    required this.id,
    required this.startTime,
    required this.endTime,
    required this.day,
    required this.dayName,
    required this.subject,
    required this.classSectionDetails,
    required this.linkName,
    required this.linkCustomUrl,
  });

  TimeTableSlot.fromJson(Map<String, dynamic> json) {
    id = json['id']?.toString() ?? '';
    startTime = json['start_time'] ?? '';
    endTime = json['end_time'] ?? '';
    day = json['day'] ?? 0;
    dayName = json['day_name'] ?? '';
    classSectionDetails =
        ClassSectionDetails.fromJson(Map.from(json['class_section'] ?? {}));
    subject = Subject.fromJson(Map.from(json['subject']['subject'] ?? {}));
    linkName = json['link_name'];
    linkCustomUrl = json['live_class_url'];
  }

  late final String id;
  late final String startTime;
  late final String endTime;
  late final int day;
  late final String dayName;
  late final Subject subject;
  late final ClassSectionDetails classSectionDetails;
  late String? linkName;
  late String? linkCustomUrl;

  bool get hasCustomLink =>
      linkCustomUrl != null &&
      linkName != null &&
      linkName!.trim().isNotEmpty &&
      linkCustomUrl!.trim().isNotEmpty;
}
