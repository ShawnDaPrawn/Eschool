class Teacher {

  Teacher.fromJson(Map<String, dynamic> json) {
    subjects = json['subjects'] ?? '';
    profileUrl = json['image'] ?? '';
    firstName = json['first_name'] ?? '';
    lastName = json['last_name'] ?? '';
    mobile = json['mobile_no'] ?? '';
    email = json['email'] ?? '';
  }
  late final String subjects;
  late final String profileUrl;
  late final String firstName;
  late final String lastName;
  late final String mobile;
  late final String email;
}
