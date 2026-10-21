class SemesterBreak {
  final DateTime startDate;
  final DateTime endDate;

  SemesterBreak({required this.startDate, required this.endDate});

  SemesterBreak.fromJson(Map<String, dynamic> json)
    : startDate = DateTime.parse(json['start']),
      endDate = DateTime.parse(json['end']);
}
