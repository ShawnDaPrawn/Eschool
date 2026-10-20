import 'package:eschool/data/models/subject.dart';

class ElectiveSubject extends Subject {

  ElectiveSubject.fromJson({
    required Map<String, dynamic> json,
    required electiveSubjectGroupId,
  }) : super.fromJson(Map.from(json)) {
    electiveSubjectGroupId = electiveSubjectGroupId;
  }
  late final int electiveSubjectGroupId;
}
