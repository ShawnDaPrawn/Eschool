import 'package:eschool_core/eschool_core.dart';

class Teacher {
  Teacher({
    required this.id,
    required this.firstName,
    required this.lastName,
    required this.gender,
    required this.email,
    required this.fcmId,
    required this.mobile,
    required this.image,
    required this.dob,
    required this.currentAddress,
    required this.permanentAddress,
    required this.status,
    required this.resetRequest,
    required this.qualification,
    required this.apiDynamicFields,
    required this.dynamicFields,
    required this.userId,
  });

  Teacher.fromJson(Map<String, dynamic> json) {
    id = json['id'] ?? 0;
    userId = json['user_id'] ?? 0;
    firstName = json['first_name'] ?? '';
    lastName = json['last_name'] ?? '';
    gender = json['gender'] ?? '';
    email = json['email'] ?? '';
    fcmId = json['fcm_id'] ?? '';
    mobile = json['mobile'] ?? '';
    image = json['image'] ?? '';
    dob = json['dob'] ?? '';
    currentAddress = json['current_address'] ?? '';
    permanentAddress = json['permanent_address'] ?? '';
    status = json['status'] ?? 0;
    resetRequest = json['reset_request'] ?? 0;
    qualification = json['qualification']?.toString() ?? '';
    apiDynamicFields = json['dynamic_fields'] is Map
        ? json['dynamic_fields']
        : null; //for json storage purposes
    dynamicFields = DynamicFieldModel.generateDynamicFieldsFromAPIValues(
      dynamicField: json['dynamic_fields'] ?? {},
    );
  }
  late final int id;
  late final String firstName;
  late final String lastName;
  late final String gender;
  late final String email;
  late final String fcmId;
  late final String mobile;
  late final String image;
  late final String dob;
  late final String currentAddress;
  late final String permanentAddress;
  late final int status;
  late final int resetRequest;
  late final String qualification;
  late final Map? apiDynamicFields;
  late final int userId;
  late final List<DynamicFieldModel>? dynamicFields;

  String getFullName() {
    return '$firstName $lastName';
  }

  Map<String, dynamic> toJson() {
    final data = <String, dynamic>{};
    data['id'] = id;
    data['user_id'] = userId;
    data['first_name'] = firstName;
    data['last_name'] = lastName;
    data['gender'] = gender;
    data['email'] = email;
    data['fcm_id'] = fcmId;
    data['mobile'] = mobile;
    data['image'] = image;
    data['dob'] = dob;
    data['current_address'] = currentAddress;
    data['permanent_address'] = permanentAddress;
    data['status'] = status;
    data['reset_request'] = resetRequest;
    data['qualification'] = qualification;
    data['dynamic_fields'] = apiDynamicFields;
    return data;
  }
}
