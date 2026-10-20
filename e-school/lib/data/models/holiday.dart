import 'package:intl/intl.dart';

class Holiday {
  Holiday({
    required this.id,
    required this.date,
    required this.title,
    required this.description,
    required this.createdAt,
    required this.updatedAt,
  });

  Holiday.fromJson(Map<String, dynamic> json) {
    id = json['id'] ?? 0;
    date = json['date'] == null
        ? DateTime.now()
        : DateFormat('yyyy-MM-dd').parse(json['date'].toString());
    title = json['title'] ?? '';
    description = json['description'] ?? '';
    createdAt = json['created_at'] ?? '';
    updatedAt = json['updated_at'] ?? '';
  }
  late final int id;
  late final DateTime date;
  late final String title;
  late final String description;
  late final String createdAt;
  late final String updatedAt;
}
