class EventSchedule {

  EventSchedule({
    required this.id,
    required this.eventId,
    required this.title,
    required this.date,
    required this.startTime,
    required this.endTime,
    required this.description,
  });

  factory EventSchedule.fromJson(Map<String, dynamic> json) {
    return EventSchedule(
      id: json['id'] ?? 0,
      eventId: json['event_id'] ?? 0,
      title: json['title'] ?? '',
      date: DateTime.parse(json['date'] ?? ''),
      startTime: json['start_time'],
      endTime: json['end_time'],
      description: json['description'] ?? '',
    );
  }
  final int id;
  final int eventId;
  final String title;
  final DateTime date;
  final String? startTime;
  final String? endTime;
  final String description;

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'event_id': eventId,
      'title': title,
      'date': date.toIso8601String().substring(0, 10),
      'start_time': startTime,
      'end_time': endTime,
      'description': description,
    };
  }

  @override
  String toString() {
    return 'EventSchedule(id: $id, eventId: $eventId, title: $title, date: $date, startTime: $startTime, endTime: $endTime, description: $description)';
  }
}
