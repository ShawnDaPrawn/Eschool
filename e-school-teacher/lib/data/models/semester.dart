class Semester {
  Semester({
    required this.id,
    required this.name,
    required this.startDate,
    required this.endDate,
  });

  factory Semester.fromJson(Map<String, dynamic> json) {
    return Semester(
      id: json['id']?.toString() ?? '',
      name: json['name']?.toString(),
      startDate: json['start_date']?.toString() ?? '',
      endDate: json['end_date']?.toString() ?? '',
    );
  }
  String id;
  String? name;
  String startDate;
  String endDate;

  Map<String, dynamic> toJson() {
    final data = <String, dynamic>{};
    data['id'] = id;
    data['name'] = name;
    data['start_date'] = startDate;
    data['end_date'] = endDate;
    return data;
  }
}
