class Holiday {
  Holiday({
    required this.id,
    required this.date,
    required this.title,
    required this.description,
  });

  Holiday.fromJson(Map<String, dynamic> json) {
    id = json['id'] ?? 0;
    date = json['date'] == null
        ? DateTime.now()
        : DateTime.parse(json['date'].toString());
    title = json['title'] ?? '';
    description = json['description'] ?? '';
  }
  late final int id;
  late final DateTime date;
  late final String title;
  late final String description;
}
