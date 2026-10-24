import 'package:eschool_teacher/data/models/term.dart';

class Subject {
  Subject({
    required this.id,
    required this.name,
    required this.code,
    required this.bgColor,
    required this.image,
    required this.mediumId,
    required this.type,
    this.term,
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

    if (json['term'] != null) {
      term = Term.fromJson(Map.from(json['term']));
    } else {
      term = null;
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
  late final Term? term;

  String get subjectNameWithTypeAndTerm => term != null
      ? type == 'Practical'
            ? '$name ($type) - ${term?.displayName}'
            : '$name - ${term?.displayName}'
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
    if (term != null) {
      data['term'] = term!.toJson();
    }
    return data;
  }
}
