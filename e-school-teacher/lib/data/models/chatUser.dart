// ignore_for_file: public_member_api_docs, sort_constructors_first
import 'dart:convert';

import 'package:eschool_teacher/data/models/chatMessage.dart';

class ChatUser {
  int id;
  int userId;
  String firstName;
  String lastName;
  String profileUrl;
  List<String>? subjectNames;
  ChatMessage? lastMessage;
  String? mobileNumber;
  String? email;
  String? occupation;
  bool isParent;
  int unreadCount;
  List<ChildSmallData>? children;
  String? className;
  String? address;
  String? dob;
  String? gender;
  int? rollNo;
  String? admissionNo;

  //getters for the UI
  int get unreadNotificationsCount => unreadCount;
  String get subjects =>
      subjectNames.toString().substring(1, subjectNames.toString().length - 1);

  String get childrenNames {
    final list = children?.map((e) => e.name).toList();
    return list?.toString().substring(1, list.toString().length - 1) ?? '';
  }

  bool get hasUnreadMessages => unreadNotificationsCount != 0;
  String get userName => '$firstName $lastName';

  ChatUser({
    required this.id,
    required this.userId,
    required this.firstName,
    required this.lastName,
    required this.profileUrl,
    required this.lastMessage, required this.mobileNumber, required this.email, required this.occupation, required this.isParent, required this.unreadCount, this.subjectNames,
    this.children,
    this.className,
    this.address,
    this.dob,
    this.gender,
    this.rollNo,
    this.admissionNo,
  });

  factory ChatUser.fromJsonAPI(Map json) {
    return ChatUser(
      id: json['id'] ?? 0,
      userId: json['user_id'] ?? 0,
      firstName: json['first_name'] ?? '',
      lastName: json['last_name'] ?? '',
      profileUrl: json['image'] ?? '',
      subjectNames: json['subjects']?.isEmpty ?? true
          ? <String>[]
          : json['subjects'].map<String>((e) => e['name'].toString()).toList(),
      lastMessage: json['last_message'] == null || json['last_message'].isEmpty
          ? null
          : ChatMessage.fromJsonAPI(json['last_message']),
      mobileNumber: json['mobile_no'],
      email: json['email'],
      occupation: json['occupation'],
      unreadCount: int.tryParse(json['unread_message'].toString()) ?? 0,
      isParent: json['isParent'].toString() == '1',
      children: json['children']?.isEmpty ?? true
          ? null
          : json['children']
              .map<ChildSmallData>((e) => ChildSmallData.fromMapAPI(e))
              .toList(),
      rollNo: json['roll_no'],
      admissionNo: json['admission_no'],
      gender: json['gender'],
      dob: json['dob'],
      address: json['address'],
      className: json['class_name'],
    );
  }

  ChatUser copyWith({
    int? id,
    int? userId,
    String? firstName,
    String? lastName,
    String? profileUrl,
    List<String>? subjectNames,
    ChatMessage? lastMessage,
    String? mobileNumber,
    String? email,
    String? occupation,
    bool? isParent,
    int? unreadCount,
    List<ChildSmallData>? children,
    String? className,
    String? address,
    String? dob,
    String? gender,
    int? rollNo,
    String? admissionNo,
  }) {
    return ChatUser(
      id: id ?? this.id,
      userId: userId ?? this.userId,
      firstName: firstName ?? this.firstName,
      lastName: lastName ?? this.lastName,
      profileUrl: profileUrl ?? this.profileUrl,
      subjectNames: subjectNames ?? this.subjectNames,
      lastMessage: lastMessage ?? this.lastMessage,
      mobileNumber: mobileNumber ?? this.mobileNumber,
      email: email ?? this.email,
      occupation: occupation ?? this.occupation,
      isParent: isParent ?? this.isParent,
      unreadCount: unreadCount ?? this.unreadCount,
      children: children ?? this.children,
      className: className ?? this.className,
      address: address ?? this.address,
      dob: dob ?? this.dob,
      gender: gender ?? this.gender,
      rollNo: rollNo ?? this.rollNo,
      admissionNo: admissionNo ?? this.admissionNo,
    );
  }

  Map<String, dynamic> toMap() {
    return <String, dynamic>{
      'id': id,
      'userId': userId,
      'firstName': firstName,
      'lastName': lastName,
      'profileUrl': profileUrl,
      'subjectNames': subjectNames,
      'lastMessage': lastMessage?.toMap(),
      'mobileNumber': mobileNumber,
      'email': email,
      'occupation': occupation,
      'isParent': isParent,
      'unreadCount': unreadCount,
      'children': children?.map((x) => x.toMap()).toList(),
      'className': className,
      'address': address,
      'dob': dob,
      'gender': gender,
      'rollNo': rollNo,
      'admissionNo': admissionNo,
    };
  }

  factory ChatUser.fromMap(Map<String, dynamic> map) {
    return ChatUser(
      id: map['id'] as int,
      userId: map['userId'] as int,
      firstName: map['firstName'] as String,
      lastName: map['lastName'] as String,
      profileUrl: map['profileUrl'] as String,
      subjectNames: map['subjectNames'] != null
          ? List<String>.from(map['subjectNames'] as List<dynamic>)
          : null,
      lastMessage: map['lastMessage'] != null
          ? ChatMessage.fromMap(map['lastMessage'] as Map<String, dynamic>)
          : null,
      mobileNumber:
          map['mobileNumber'] != null ? map['mobileNumber'] as String : null,
      email: map['email'] != null ? map['email'] as String : null,
      occupation:
          map['occupation'] != null ? map['occupation'] as String : null,
      isParent: map['isParent'] as bool,
      unreadCount: map['unreadCount'] as int,
      children: map['children'] != null
          ? List<ChildSmallData>.from(
              (map['children'] as List<Map<String, dynamic>>)
                  .map<ChildSmallData?>(
                (x) => ChildSmallData.fromMap(x),
              ),
            )
          : null,
      className: map['className'] != null ? map['className'] as String : null,
      address: map['address'] != null ? map['address'] as String : null,
      dob: map['dob'] != null ? map['dob'] as String : null,
      gender: map['gender'] != null ? map['gender'] as String : null,
      rollNo: map['rollNo'] != null ? map['rollNo'] as int : null,
      admissionNo:
          map['admissionNo'] != null ? map['admissionNo'] as String : null,
    );
  }

  String toJson() => json.encode(toMap());

  factory ChatUser.fromJson(String source) =>
      ChatUser.fromMap(json.decode(source) as Map<String, dynamic>);

  @override
  String toString() {
    return 'ChatUser(id: $id, userId: $userId, firstName: $firstName, lastName: $lastName, profileUrl: $profileUrl, subjectNames: $subjectNames, lastMessage: $lastMessage, mobileNumber: $mobileNumber, email: $email, occupation: $occupation, isParent: $isParent, unreadCount: $unreadCount, children: $children, className: $className, address: $address, dob: $dob, gender: $gender, rollNo: $rollNo, admissionNo: $admissionNo)';
  }
}

class ChildSmallData {
  int id;
  int userId;
  String name;
  String className;
  String admissionNo;
  String profileUrl;
  List<String> subjects;

  String get subjectsString =>
      subjects.toString().substring(1, subjects.toString().length - 1);

  ChildSmallData({
    required this.id,
    required this.userId,
    required this.name,
    required this.className,
    required this.admissionNo,
    required this.profileUrl,
    required this.subjects,
  });

  ChildSmallData copyWith({
    int? id,
    int? userId,
    String? name,
    String? className,
    String? admissionNo,
    String? profileUrl,
    List<String>? subjects,
  }) {
    return ChildSmallData(
      id: id ?? this.id,
      userId: userId ?? this.userId,
      name: name ?? this.name,
      className: className ?? this.className,
      admissionNo: admissionNo ?? this.admissionNo,
      profileUrl: profileUrl ?? this.profileUrl,
      subjects: subjects ?? this.subjects,
    );
  }

  Map<String, dynamic> toMap() {
    return <String, dynamic>{
      'id': id,
      'user_id': userId,
      'child_name': name,
      'class_name': className,
      'admission_no': admissionNo,
      'image': profileUrl,
      'subjects': subjects,
    };
  }

  factory ChildSmallData.fromMapAPI(Map<String, dynamic> map) {
    return ChildSmallData(
      id: map['id'] as int,
      userId: map['user_id'] as int,
      name: map['child_name'] as String,
      className: map['class_name'] as String,
      admissionNo: map['admission_no'] as String,
      profileUrl: map['image'] as String,
      subjects: map['subject']?.isEmpty ?? true
          ? <String>[]
          : map['subject'].map<String>((e) => e['name'].toString()).toList(),
    );
  }

  factory ChildSmallData.fromMap(Map<String, dynamic> map) {
    return ChildSmallData(
      id: map['id'] as int,
      userId: map['user_id'] as int,
      name: map['child_name'] as String,
      className: map['class_name'] as String,
      admissionNo: map['admission_no'] as String,
      profileUrl: map['image'] as String,
      subjects: List<String>.from(
        map['subjects'] as List<String>,
      ),
    );
  }

  String toJson() => json.encode(toMap());

  factory ChildSmallData.fromJson(String source) =>
      ChildSmallData.fromMap(json.decode(source) as Map<String, dynamic>);

  @override
  String toString() {
    return 'ChildSmallData(id: $id, userId: $userId, name: $name, className: $className, admissionNo: $admissionNo, profileUrl: $profileUrl, subjects: $subjects)';
  }
}
