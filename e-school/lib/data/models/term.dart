class Term {
  Term({
    required this.id,
    required this.name,
    required this.startDate,
    required this.endDate,
    this.gradeFrom = 0,
    this.gradeTo = 12,
  });

  factory Term.fromJson(Map<String, dynamic> json) {
    return Term(
      gradeFrom: int.tryParse('${json['grade_from']}') ?? 0,
      gradeTo: int.tryParse('${json['grade_to']}') ?? 12,
      id: json['id']?.toString() ?? '',
      name: json['name']?.toString(),
      startDate: json['start_date']?.toString() ?? '',
      endDate: json['end_date']?.toString() ?? '',
    );
  }
  final int gradeFrom;
  final int gradeTo;
  String get displayName => gradeFrom == 0 && gradeTo == 12
      ? name ?? ''
      : "${name ?? ''} (${gradeFrom == 0 ? 'R' : gradeFrom}–$gradeTo)";
  String id;
  String? name;
  String startDate;
  String endDate;

  @override
  bool operator ==(Object other) {
    return other is Term && other.id == id;
  }

  @override
  int get hashCode => id.hashCode;
}
