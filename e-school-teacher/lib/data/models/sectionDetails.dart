class SectionDetails {
  SectionDetails({
    required this.id,
    required this.name,
  });

  SectionDetails.fromJson(Map<String, dynamic> json, String? postfix) {
    id = json['id'];
    if (postfix != null) {
      name = json['name'] + postfix;
    } else {
      name = json['name'];
    }
  }
  late final int id;
  late final String name;

  Map<String, dynamic> toJson() {
    final data = <String, dynamic>{};
    data['id'] = id;
    data['name'] = name;
    return data;
  }
}
