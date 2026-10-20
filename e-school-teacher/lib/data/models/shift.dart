class Shift {
  Shift({
    required this.id,
    required this.title,
  });

  Shift.fromJson(Map<String, dynamic> json) {
    id = json['id'];
    title = json['title'];
    startTime = json['start_time'];
    endTime = json['end_time'];
  }

  late final int id;
  late final String title;
  late final String? startTime;
  late final String? endTime;

  Map<String, dynamic> toJson() {
    final data = <String, dynamic>{};
    data['id'] = id;
    data['title'] = title;
    data['start_time'] = startTime;
    data['end_time'] = endTime;
    return data;
  }
}
