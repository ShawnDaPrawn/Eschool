class StaffLeave {

  StaffLeave({
    required this.userName,
    required this.role,
    required this.type,
    required this.date,
    required this.image,
  });

  StaffLeave.fromJson(Map<String, dynamic> json)
      : userName = json['user_name'] ?? '',
        role = json['role'] ?? '',
        type = json['type'] ?? '',
        date = json['date'] ?? '',
        image = json['image'] ?? '';
  final String userName;
  final String role;
  final String type;
  final String date;
  final String image;
}
