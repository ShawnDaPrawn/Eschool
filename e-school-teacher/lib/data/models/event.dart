class Event {

  Event({
    required this.id,
    required this.title,
    required this.type,
    required this.description, required this.hasDaySchedule, this.startTime,
    this.endTime,
    this.image,
    this.startDate,
    this.endDate,
    this.multipleEvent,
  });

  factory Event.fromJson(Map<String, dynamic> json) {
    return Event(
      id: json['id'] ?? 0,
      title: json['title'] ?? '',
      type: json['type'] ?? '',
      startTime: json['start_time'],
      endTime: json['end_time'],
      image: json['image'],
      startDate: json['start_date'] != null
          ? DateTime.parse(json['start_date'])
          : null,
      hasDaySchedule: json['has_day_schedule'] == 1,
      endDate:
          json['end_date'] != null ? DateTime.parse(json['end_date']) : null,
      description: json['description'] ?? '',
      multipleEvent: (json['multiple_event'] as List<dynamic>?)
          ?.map((eventJson) => Event.fromJson(eventJson))
          .toList(),
    );
  }
  final int id;
  final String title;
  final String type;
  final String? startTime;
  final String? endTime;
  final String? image;
  final DateTime? startDate;
  final DateTime? endDate;
  final String description;
  final List<Event>? multipleEvent;
  final bool hasDaySchedule;

  bool get isSingleDayEvent => type.toLowerCase() == 'single';

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'title': title,
      'type': type,
      'start_time': startTime,
      'end_time': endTime,
      'image': image,
      'start_date': startDate?.toIso8601String(),
      'end_date': endDate?.toIso8601String(),
      'description': description,
      'multiple_event': multipleEvent?.map((event) => event.toJson()).toList(),
      'has_day_schedule': hasDaySchedule ? 1 : 0,
    };
  }

  @override
  String toString() {
    return 'Event(id: $id, title: $title, type: $type, startTime: $startTime, endTime: $endTime, image: $image, startDate: $startDate, endDate: $endDate, description: $description, hasDaySchedules: $hasDaySchedule, multipleEvent: $multipleEvent)';
  }
}
