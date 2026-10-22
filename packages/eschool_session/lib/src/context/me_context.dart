import 'package:equatable/equatable.dart';

/// What `GET /api/v2/me/context` says about the signed-in user: who they are,
/// their schools and roles, the school they are working in (its branding and
/// timezone), the modules switched on there, a guardian's children, a
/// learner's approval and the notification rules. The apps fetch it at
/// start-up, after signing in and on changing school, and keep the last
/// answer.
///
/// The reader is lenient: a missing or oddly shaped field becomes empty or
/// `null` ("not available yet") rather than failing, and keys it does not
/// know are ignored, so a newer or older server never stops the app. Each
/// work stream of the server adds its own keys; [has] says which arrived.
class MeContext extends Equatable {
  const MeContext({
    required this.user,
    required this.roles,
    required this.school,
    required this.modules,
    this.schools = const [],
    this.children,
    this.approval,
    this.branding,
    this.notificationRules,
    this.featureFlags = const {},
    this.signInMethods,
    this.keys = const {},
  });

  factory MeContext.fromJson(Map<String, dynamic> json) {
    final Map<String, dynamic> user = _map(json['user']);
    final Object? currentSchool = json['current_school'] ?? json['school'];
    final List<MeSchoolMembership> schools = _list(
      json['schools'],
    ).map(MeSchoolMembership.fromJson).toList();
    final MeSchool? school = currentSchool is Map
        ? MeSchool.fromJson(_map(currentSchool))
        : null;
    List<String> roles = _names(json['roles'], nameKey: 'name');
    if (roles.isEmpty) {
      roles = _names(user['roles'], nameKey: 'name');
    }
    if (roles.isEmpty && school != null) {
      roles = [
        for (final MeSchoolMembership membership in schools)
          if (membership.id == school.id) ...membership.roles,
      ];
    }
    return MeContext(
      user: MeUser.fromJson(user),
      roles: roles,
      school: school,
      modules: _enabledModules(json['modules']),
      schools: schools,
      children: json['children'] is List
          ? _list(json['children']).map(MeChild.fromJson).toList()
          : null,
      approval: json['approval'] is Map
          ? MeApproval.fromJson(_map(json['approval']))
          : null,
      branding: json['branding'] is Map
          ? MeBranding.fromJson(_map(json['branding']))
          : null,
      notificationRules: json['notification_rules'] is List
          ? _list(
              json['notification_rules'],
            ).map(MeNotificationRule.fromJson).toList()
          : null,
      featureFlags: _flags(json['feature_flags']),
      signInMethods: json['sign_in_methods'] is List
          ? _names(json['sign_in_methods'], nameKey: 'method')
          : null,
      keys: json.keys.toSet(),
    );
  }

  final MeUser user;
  final List<String> roles;

  /// The school the user is working in; `null` when the server sent none.
  final MeSchool? school;

  /// Keys of the modules switched on for that school (`config/modules.php` on
  /// the server). Empty until the server sends them.
  final List<String> modules;

  /// Every school the user belongs to, with their roles there. More than
  /// one shows the school switcher.
  final List<MeSchoolMembership> schools;

  /// A guardian's children, each with their school. `null` when the server
  /// did not send the key (the apps then use the sign-in answer).
  final List<MeChild>? children;

  /// A learner's guardian approval; `null` when not sent.
  final MeApproval? approval;

  /// The current school's name and crest as the apps show them beside
  /// NavigateEd's own brand; `null` when not sent.
  final MeBranding? branding;

  /// The school's notification rules for this profile; `null` when not sent.
  final List<MeNotificationRule>? notificationRules;

  final Map<String, bool> featureFlags;

  /// How the user may sign in at this school; `null` when not sent.
  final List<String>? signInMethods;

  /// The top-level keys the server sent.
  final Set<String> keys;

  /// Whether the server sent [key]: a key that is missing means the server
  /// has not got that part yet, not that it is empty.
  bool has(String key) => keys.contains(key);

  bool hasRole(String role) => roles.contains(role);

  bool isModuleOn(String key) => modules.contains(key);

  bool isFeatureOn(String key) => featureFlags[key] ?? false;

  /// The id of the school the user is working in, or `null`.
  int? get currentSchoolId =>
      school == null || school!.id == 0 ? null : school!.id;

  Map<String, dynamic> toJson() => {
    'user': user.toJson(),
    'roles': roles,
    if (school != null) 'current_school': school!.toJson(),
    'modules': modules,
    'schools': [for (final MeSchoolMembership s in schools) s.toJson()],
    if (children != null)
      'children': [for (final MeChild child in children!) child.toJson()],
    if (approval != null) 'approval': approval!.toJson(),
    if (branding != null) 'branding': branding!.toJson(),
    if (notificationRules != null)
      'notification_rules': [
        for (final MeNotificationRule rule in notificationRules!) rule.toJson(),
      ],
    'feature_flags': featureFlags,
    if (signInMethods != null) 'sign_in_methods': signInMethods,
  };

  @override
  List<Object?> get props => [
    user,
    roles,
    school,
    modules,
    schools,
    children,
    approval,
    branding,
    notificationRules,
    featureFlags,
    signInMethods,
  ];
}

class MeUser extends Equatable {
  const MeUser({
    required this.id,
    required this.name,
    required this.email,
    required this.image,
    this.gender = '',
  });

  factory MeUser.fromJson(Map<String, dynamic> json) {
    final String fullName = [
      json['first_name'],
      json['last_name'],
    ].where((part) => part != null && '$part'.isNotEmpty).join(' ');
    return MeUser(
      id: _int(json['id']),
      name: '${json['name'] ?? fullName}',
      email: _string(json['email']),
      image: _string(json['avatar_url'] ?? json['image']),
      gender: _string(json['gender']),
    );
  }

  final int id;
  final String name;
  final String email;

  /// The photo's or avatar's URL, or an avatar key; empty when none.
  final String image;
  final String gender;

  Map<String, dynamic> toJson() => {
    'id': id,
    'name': name,
    'email': email,
    'image': image,
    if (gender.isNotEmpty) 'gender': gender,
  };

  @override
  List<Object?> get props => [id, name, email, image, gender];
}

/// The school the user is working in.
class MeSchool extends Equatable {
  const MeSchool({
    required this.id,
    required this.name,
    required this.timezone,
    required this.branding,
    this.shortName = '',
    this.crestUrl = '',
  });

  factory MeSchool.fromJson(Map<String, dynamic> json) {
    final Map<String, dynamic> branding = Map<String, dynamic>.from(
      _map(json['branding']),
    );
    return MeSchool(
      id: _int(json['id']),
      name: _string(json['name']),
      timezone: _string(json['timezone']),
      branding: branding,
      shortName: _string(json['short_name']),
      crestUrl: _string(
        json['crest_url'] ??
            branding['crest_url'] ??
            branding['logo'] ??
            branding['logo_url'],
      ),
    );
  }

  final int id;
  final String name;
  final String shortName;

  /// The crest's absolute URL; empty when the school has none.
  final String crestUrl;

  /// An IANA name such as `Africa/Johannesburg`; empty when not sent.
  final String timezone;

  /// The school's logo and colours as the server sends them. Shown beside
  /// NavigateEd's own brand, never instead of it: the app theme does not read
  /// these.
  final Map<String, dynamic> branding;

  String get logoUrl => crestUrl;

  Map<String, dynamic> toJson() => {
    'id': id,
    'name': name,
    'short_name': shortName,
    'crest_url': crestUrl,
    'timezone': timezone,
    'branding': branding,
  };

  @override
  List<Object?> get props => [
    id,
    name,
    shortName,
    crestUrl,
    timezone,
    branding,
  ];
}

/// One school the user belongs to, with their roles there. Read from
/// `{id, name, short_name, crest_url, roles}` or, as the first API v2
/// servers sent it, `{school: {...}, roles}`.
class MeSchoolMembership extends Equatable {
  const MeSchoolMembership({
    required this.id,
    required this.name,
    this.shortName = '',
    this.crestUrl = '',
    this.roles = const [],
  });

  factory MeSchoolMembership.fromJson(Map<String, dynamic> json) {
    final Map<String, dynamic> school = json['school'] is Map
        ? _map(json['school'])
        : json;
    return MeSchoolMembership(
      id: _int(school['id']),
      name: _string(school['name']),
      shortName: _string(school['short_name']),
      crestUrl: _string(school['crest_url']),
      roles: _names(json['roles'], nameKey: 'name'),
    );
  }

  final int id;
  final String name;
  final String shortName;
  final String crestUrl;
  final List<String> roles;

  Map<String, dynamic> toJson() => {
    'id': id,
    'name': name,
    'short_name': shortName,
    'crest_url': crestUrl,
    'roles': roles,
  };

  @override
  List<Object?> get props => [id, name, shortName, crestUrl, roles];
}

/// One of a guardian's children. Read from the contract's
/// `{id, user_id, name, school_id, school_name, student_number, approved,
/// avatar_url, homeroom}`, or from the first API v2 shape with a nested
/// `school` and `image`. [id] is the learner's id, the `child_id` the v1
/// parent endpoints take.
class MeChild extends Equatable {
  const MeChild({
    required this.id,
    required this.name,
    this.userId = 0,
    this.schoolId = 0,
    this.schoolName = '',
    this.studentNumber = '',
    this.approved,
    this.avatarUrl = '',
    this.homeroom = '',
  });

  factory MeChild.fromJson(Map<String, dynamic> json) {
    final Map<String, dynamic> school = _map(json['school']);
    final String fullName = [
      json['first_name'],
      json['last_name'],
    ].where((part) => part != null && '$part'.isNotEmpty).join(' ');
    final Object? homeroom = json['homeroom'];
    final Object? approved = json['approved'];
    return MeChild(
      id: _int(json['id']),
      userId: _int(json['user_id']),
      name: _string(json['name'] ?? fullName),
      schoolId: _int(json['school_id'] ?? school['id']),
      schoolName: _string(json['school_name'] ?? school['name']),
      studentNumber: _string(json['student_number']),
      approved: approved is bool
          ? approved
          : (approved is num ? approved != 0 : null),
      avatarUrl: _string(json['avatar_url'] ?? json['image']),
      homeroom: homeroom is Map ? _string(homeroom['name']) : _string(homeroom),
    );
  }

  final int id;
  final int userId;
  final String name;

  /// 0 when not known (a server that does not send it).
  final int schoolId;
  final String schoolName;
  final String studentNumber;

  /// Whether a guardian has approved the child's access; `null` when the
  /// server does not say.
  final bool? approved;
  final String avatarUrl;
  final String homeroom;

  Map<String, dynamic> toJson() => {
    'id': id,
    'user_id': userId,
    'name': name,
    'school_id': schoolId,
    'school_name': schoolName,
    'student_number': studentNumber,
    'approved': approved,
    'avatar_url': avatarUrl,
    'homeroom': homeroom,
  };

  @override
  List<Object?> get props => [
    id,
    userId,
    name,
    schoolId,
    schoolName,
    studentNumber,
    approved,
    avatarUrl,
    homeroom,
  ];
}

/// A learner's guardian approval.
class MeApproval extends Equatable {
  const MeApproval({required this.status, this.approvedByCount = 0});

  factory MeApproval.fromJson(Map<String, dynamic> json) => MeApproval(
    status: _string(json['status']),
    approvedByCount: _int(json['approved_by_count']),
  );

  /// `pending`, `approved` or `withdrawn`.
  final String status;
  final int approvedByCount;

  bool get isApproved => status == 'approved';

  Map<String, dynamic> toJson() => {
    'status': status,
    'approved_by_count': approvedByCount,
  };

  @override
  List<Object?> get props => [status, approvedByCount];
}

/// The current school's name and crest.
class MeBranding extends Equatable {
  const MeBranding({
    required this.displayName,
    this.shortName = '',
    this.crestUrl = '',
  });

  factory MeBranding.fromJson(Map<String, dynamic> json) => MeBranding(
    displayName: _string(json['display_name']),
    shortName: _string(json['short_name']),
    crestUrl: _string(json['crest_url']),
  );

  final String displayName;
  final String shortName;
  final String crestUrl;

  Map<String, dynamic> toJson() => {
    'display_name': displayName,
    'short_name': shortName,
    'crest_url': crestUrl,
  };

  @override
  List<Object?> get props => [displayName, shortName, crestUrl];
}

/// How the school sends one notification type on one channel.
class MeNotificationRule extends Equatable {
  const MeNotificationRule({
    required this.type,
    required this.channel,
    required this.mode,
  });

  factory MeNotificationRule.fromJson(Map<String, dynamic> json) =>
      MeNotificationRule(
        type: _string(json['type']),
        channel: _string(json['channel']),
        mode: _string(json['mode']),
      );

  final String type;

  /// `push`, `email` or `in_app`.
  final String channel;

  /// `mandatory`, `default_on`, `default_off` or `not_offered`.
  final String mode;

  Map<String, dynamic> toJson() => {
    'type': type,
    'channel': channel,
    'mode': mode,
  };

  @override
  List<Object?> get props => [type, channel, mode];
}

Map<String, dynamic> _map(Object? value) =>
    value is Map ? Map<String, dynamic>.from(value) : const {};

List<Map<String, dynamic>> _list(Object? value) => value is List
    ? [
        for (final Object? item in value)
          if (item is Map) Map<String, dynamic>.from(item),
      ]
    : const [];

int _int(Object? value) => value is int ? value : int.tryParse('$value') ?? 0;

String _string(Object? value) => value == null ? '' : '$value';

Map<String, bool> _flags(Object? value) => value is Map
    ? {
        for (final MapEntry<dynamic, dynamic> entry in value.entries)
          '${entry.key}': entry.value == true || entry.value == 1,
      }
    : const {};

/// A list of names, given as strings or as objects carrying the name.
List<String> _names(Object? value, {required String nameKey}) {
  if (value is! List) {
    return const [];
  }
  return [
    for (final item in value)
      if (item is String)
        item
      else if (item is Map && item[nameKey] != null)
        '${item[nameKey]}',
  ];
}

/// Module keys that are on: `["chat", ...]`, `[{"key": "chat", "enabled":
/// true}, ...]` or `{"chat": true, ...}`.
List<String> _enabledModules(Object? value) {
  if (value is Map) {
    return [
      for (final entry in value.entries)
        if (entry.value == true || entry.value == 1) '${entry.key}',
    ];
  }
  if (value is! List) {
    return const [];
  }
  return [
    for (final item in value)
      if (item is String)
        item
      else if (item is Map &&
          item['key'] != null &&
          (item['enabled'] ?? true) != false &&
          item['enabled'] != 0)
        '${item['key']}',
  ];
}
