import 'package:eschool/data/models/subject.dart';

class CoreSubject extends Subject {

  CoreSubject.fromJson({required Map<String, dynamic> json})
      : super.fromJson(Map.from(json['subject'] ?? {})) {
    classId = json['class_id'] ?? 0;
  }
  late final int classId;
}
