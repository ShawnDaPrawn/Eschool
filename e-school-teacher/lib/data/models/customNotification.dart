class CustomNotification {

  CustomNotification({
    required this.id,
    required this.title,
    required this.type,
    required this.message,
    required this.date, this.sendTo,
    this.image,
    this.createdAt,
    this.updatedAt,
    this.deletedAt,
  });

  factory CustomNotification.fromJson(Map<String, dynamic> json) {
    return CustomNotification(
      id: json['id'] ?? 0,
      title: json['title'] ?? '',
      type: json['type'] ?? 'custom',
      message: json['message'] ?? '',
      sendTo: json['send_to'],
      image: json['image'],
      date: DateTime.tryParse(json['date']) ?? DateTime.now(),
      createdAt: json['created_at'],
      updatedAt: json['updated_at'],
      deletedAt: json['deleted_at'],
    );
  }
  int id;
  String title;
  String type;
  String message;
  int? sendTo;
  String? image;
  DateTime date;
  String? createdAt;
  String? updatedAt;
  String? deletedAt;

  Map<String, dynamic> toJson() {
    final Map<String, dynamic> data = <String, dynamic>{};
    data['id'] = id;
    data['title'] = title;
    data['type'] = type;
    data['message'] = message;
    data['send_to'] = sendTo;
    data['image'] = image;
    data['date'] = date;
    data['created_at'] = createdAt;
    data['updated_at'] = updatedAt;
    data['deleted_at'] = deletedAt;
    return data;
  }
}
