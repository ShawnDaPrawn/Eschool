class StudentClass {

  StudentClass({required this.medium, this.id, this.name, this.mediumId});
  StudentClass.fromJson(Map<String, dynamic> json) {
    id = json['id'];
    name = json['name'];
    mediumId = json['medium_id'];
    medium = Medium.fromJson(json['medium'] ?? {});
  }
  late final int? id;
  late final String? name;
  late final int? mediumId;
  late final Medium medium;
}

class Medium {

  Medium({this.id, this.name});
  Medium.fromJson(Map<String, dynamic> json) {
    id = json['id'];
    name = json['name'];
  }
  late final int? id;
  late final String? name;
}
