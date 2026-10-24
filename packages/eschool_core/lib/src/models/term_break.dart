class TermBreak {
  final DateTime startDate;
  final DateTime endDate;

  TermBreak({required this.startDate, required this.endDate});

  TermBreak.fromJson(Map<String, dynamic> json)
    : startDate = DateTime.parse(json['start']),
      endDate = DateTime.parse(json['end']);
}
