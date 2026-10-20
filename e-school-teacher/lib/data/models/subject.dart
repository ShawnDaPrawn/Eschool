import 'package:eschool_teacher/data/models/semester.dart';

class Subject {
  Subject({
    required this.id,
    required this.name,
    required this.code,
    required this.bgColor,
    required this.image,
    required this.mediumId,
    required this.type,
    this.semester,
  });

  Subject.fromJson(Map<String, dynamic> json) {
    id = json['id'] ?? 0;
    name = json['name'] ?? '';
    code = json['code'] ?? '';
    bgColor = json['bg_color'] ?? '';
    image = json['image'] ?? '';
    mediumId = json['medium_id'] ?? 0;
    type = json['type'] ?? '';
    final imageUrlParts = image.split('.');
    isSubjectImageSvg = imageUrlParts.last.toLowerCase() == 'svg';

    if (json['semester'] != null) {
      semester = Semester.fromJson(Map.from(json['semester']));
    } else {
      semester = null;
    }
  }
  late final int id;
  late final String name;
  late final String code;
  late final String bgColor;
  late final String image;
  late final int mediumId;
  late final String type;
  late final bool isSubjectImageSvg;
  late final Semester? semester;

  String get subjectNameWithTypeAndSemester => semester != null
      ? type == 'Practical'
            ? '$name ($type) - ${semester?.name}'
            : '$name - ${semester?.name}'
      : type == 'Practical'
      ? '$name ($type)'
      : name;

  Map<String, dynamic> toJson() {
    final data = <String, dynamic>{};
    data['id'] = id;
    data['name'] = name;
    data['code'] = code;
    data['bg_color'] = bgColor;
    data['image'] = image;
    data['medium_id'] = mediumId;
    data['type'] = type;
    if (semester != null) {
      data['semester'] = semester!.toJson();
    }
    return data;
  }
}
