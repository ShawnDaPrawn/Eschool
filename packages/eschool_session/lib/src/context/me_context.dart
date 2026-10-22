import 'package:equatable/equatable.dart';

/// What `GET /api/v2/me/context` says about the signed-in user: who they are,
/// their roles, the school they are working in (its branding and timezone)
/// and the modules switched on there. The apps read it on launch, after
/// sign-in and after a school switch, and keep the last answer.
///
/// The reader is lenient: a missing or oddly shaped field becomes empty
/// rather than failing, so a newer or older server never stops the app.
class MeContext extends Equatable {
  const MeContext({
    required this.user,
    required this.roles,
    required this.school,
    required this.modules,
  });

  factory MeContext.fromJson(Map<String, dynamic> json) {
    return MeContext(
      user: MeUser.fromJson(_map(json['user'])),
      roles: _names(json['roles'], nameKey: 'name'),
      school: json['school'] == null && json['current_school'] == null
          ? null
          : MeSchool.fromJson(_map(json['school'] ?? json['current_school'])),
      modules: _enabledModules(json['modules']),
    );
  }

  final MeUser user;
  final List<String> roles;

  /// The school the user is working in; `null` when the server sent none.
  final MeSchool? school;

  /// Keys of the modules switched on for that school (`config/modules.php` on
  /// the server). Empty in Phase 0.
  final List<String> modules;

  bool hasRole(String role) => roles.contains(role);

  bool isModuleOn(String key) => modules.contains(key);

  Map<String, dynamic> toJson() => {
    'user': user.toJson(),
    'roles': roles,
    'school': school?.toJson(),
    'modules': modules,
  };

  @override
  List<Object?> get props => [user, roles, school, modules];
}

class MeUser extends Equatable {
  const MeUser({
    required this.id,
    required this.name,
    required this.email,
    required this.image,
  });

  factory MeUser.fromJson(Map<String, dynamic> json) {
    final String fullName = [
      json['first_name'],
      json['last_name'],
    ].where((part) => part != null && '$part'.isNotEmpty).join(' ');
    return MeUser(
      id: _int(json['id']),
      name: '${json['name'] ?? fullName}',
      email: '${json['email'] ?? ''}',
      image: '${json['image'] ?? ''}',
    );
  }

  final int id;
  final String name;
  final String email;
  final String image;

  Map<String, dynamic> toJson() => {
    'id': id,
    'name': name,
    'email': email,
    'image': image,
  };

  @override
  List<Object?> get props => [id, name, email, image];
}

class MeSchool extends Equatable {
  const MeSchool({
    required this.id,
    required this.name,
    required this.timezone,
    required this.branding,
  });

  factory MeSchool.fromJson(Map<String, dynamic> json) {
    return MeSchool(
      id: _int(json['id']),
      name: '${json['name'] ?? ''}',
      timezone: '${json['timezone'] ?? ''}',
      branding: Map<String, dynamic>.from(_map(json['branding'])),
    );
  }

  final int id;
  final String name;

  /// An IANA name such as `Africa/Johannesburg`; empty when not sent.
  final String timezone;

  /// The school's logo and colours as the server sends them. Shown beside
  /// NavigateEd's own brand, never instead of it: the app theme does not read
  /// these.
  final Map<String, dynamic> branding;

  String get logoUrl => '${branding['logo'] ?? branding['logo_url'] ?? ''}';

  Map<String, dynamic> toJson() => {
    'id': id,
    'name': name,
    'timezone': timezone,
    'branding': branding,
  };

  @override
  List<Object?> get props => [id, name, timezone, branding];
}

Map<String, dynamic> _map(Object? value) =>
    value is Map ? Map<String, dynamic>.from(value) : const {};

int _int(Object? value) => value is int ? value : int.tryParse('$value') ?? 0;

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
