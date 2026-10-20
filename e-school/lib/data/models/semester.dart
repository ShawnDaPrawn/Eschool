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

  @override
  bool operator ==(Object other) {
    return other is Semester && other.id == id;
  }

  @override
  int get hashCode => id.hashCode;
}
