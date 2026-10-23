import 'package:equatable/equatable.dart';
import 'package:eschool_api/eschool_api.dart';

/// What `GET /api/v2/me/context` says about the signed-in user: who they are
/// and their roles, their schools, the school they are working in (its
/// crest, branding, timezone and year), the modules switched on there, the
/// notification rules, a guardian's children and a learner's approval. The
/// apps fetch it at start-up, after signing in, after a school switch, on
/// resume when it is old and on a `context.changed` push, and keep the last
/// answer.
///
/// Each part is decoded with the model generated from `openapi.yaml`
/// (`packages/eschool_api`), on its own: a part that is missing or does not
/// decode becomes `null` or empty ("not available yet") instead of failing
/// the whole answer, and keys the app does not know are ignored, so a newer
/// or older server never stops the app. The fields Phase 1 adds before the
/// contract has them ([MeChild]'s extras, [MeApproval]) are read by hand and
/// are to be replaced by the generated model when `openapi.yaml` has them.
class MeContext extends Equatable {
  const MeContext._({
    required this.raw,
    required this.user,
    required this.schools,
    required this.currentSchool,
    required this.modules,
    required this.notificationRules,
    required this.featureFlags,
    required this.signInMethods,
    required this.children,
    required this.approval,
  });

  factory MeContext.fromJson(Map<String, dynamic> json) {
    final Map<String, dynamic> raw = Map<String, dynamic>.unmodifiable(json);
    return MeContext._(
      raw: raw,
      user: _decode(json['user'], ContextUser.fromJson),
      schools:
          _decodeList(json['schools'], SchoolMembership.fromJson) ?? const [],
      currentSchool: _decode(json['current_school'], CurrentSchool.fromJson),
      modules: _decodeList(json['modules'], Module.fromJson) ?? const [],
      notificationRules: _decodeList(
        json['notification_rules'],
        NotificationRule.fromJson,
      ),
      featureFlags: _flags(json['feature_flags']),
      signInMethods: json['sign_in_methods'] is List
          ? [
              for (final Object? method in json['sign_in_methods'] as List)
                if (method is String) method,
            ]
          : null,
      children: _decodeList(json['children'], MeChild.fromJson),
      approval: _decode(json['approval'], MeApproval.fromJson),
    );
  }

  /// The answer as the server sent it: what the cache keeps.
  final Map<String, dynamic> raw;

  /// The user, with their roles at the current school; `null` when the
  /// answer had none.
  final ContextUser? user;

  /// Every school the user belongs to, with their roles there. More than
  /// one shows the school switcher.
  final List<SchoolMembership> schools;

  /// The school the user is working in: name, short name, crest, tagline,
  /// branding, timezone and year. `null` when not sent.
  final CurrentSchool? currentSchool;

  /// The modules switched on at the current school.
  final List<Module> modules;

  /// The current school's notification rules for this profile; `null` when
  /// not sent.
  final List<NotificationRule>? notificationRules;

  final Map<String, bool> featureFlags;

  /// How the user may sign in at this school; `null` when not sent.
  final List<String>? signInMethods;

  /// A guardian's children, each with their school. `null` when the server
  /// did not send the key (the apps then use the v1 sign-in answer).
  final List<MeChild>? children;

  /// A learner's guardian approval; `null` for everyone else and before the
  /// server sends it.
  final MeApproval? approval;

  /// Whether the server sent [key]: a key that is missing means the server
  /// has not got that part yet, not that it is empty.
  bool has(String key) => raw.containsKey(key);

  /// The roles at the current school (`user.roles`).
  List<String> get roles => user?.roles ?? const [];

  bool hasRole(String role) => roles.contains(role);

  /// Keys of the modules switched on.
  List<String> get moduleKeys => [
    for (final Module module in modules) module.key,
  ];

  bool isModuleOn(String key) => modules.any((module) => module.key == key);

  /// A module switched to read-only (a money module switched off with
  /// balances): its screens show, without the actions that change things.
  bool isModuleReadOnly(String key) =>
      modules.any((module) => module.key == key && module.readOnly);

  bool isFeatureOn(String key) => featureFlags[key] ?? false;

  /// The id of the school the user is working in, or `null`.
  int? get currentSchoolId => currentSchool?.id;

  Map<String, dynamic> toJson() => raw;

  @override
  List<Object?> get props => [raw];
}

/// One of a guardian's children: the contract's [Child] (generated) plus
/// what Phase 1 adds to it. [id] is the learner's id, the `child_id` the v1
/// parent endpoints take; [schoolId] is what their screens send as
/// `X-School-Id`.
class MeChild extends Equatable {
  const MeChild({
    required this.child,
    this.studentNumber,
    this.approved,
    this.homeroom,
  });

  /// Decode the additive identity fields through the generated contract.
  factory MeChild.fromJson(Map<String, dynamic> json) {
    final child = Child.fromJson(json);
    return MeChild(
      child: child,
      studentNumber: child.studentNumber,
      approved: child.approved,
      homeroom: child.homeroom,
    );
  }

  /// A child the app knows from somewhere else (the v1 parent sign-in): the
  /// same model, with the school when it is known.
  factory MeChild.of({
    required int id,
    required String name,
    int userId = 0,
    int schoolId = 0,
    String schoolName = '',
    String? image,
    String? homeroom,
    String? studentNumber,
  }) {
    final List<String> names = name.trim().split(RegExp(r'\s+'));
    return MeChild(
      child: Child(
        id: id,
        userId: userId,
        firstName: names.first,
        lastName: names.skip(1).join(' '),
        name: name,
        image: image == null || image.isEmpty ? null : image,
        homeroom: homeroom == null || homeroom.isEmpty ? null : homeroom,
        studentNumber: studentNumber,
        school: SchoolSummary(
          id: schoolId,
          name: schoolName,
          shortName: null,
          crestUrl: null,
        ),
      ),
      homeroom: homeroom == null || homeroom.isEmpty ? null : homeroom,
      studentNumber: studentNumber,
    );
  }

  final Child child;

  /// The lifelong student number; `null` until the server sends it.
  final String? studentNumber;

  /// Whether a guardian has approved the child's access; `null` until the
  /// server says.
  final bool? approved;

  /// The child's homeroom ("Grade 7 A"); `null` when not sent.
  final String? homeroom;

  int get id => child.id;
  int get userId => child.userId;
  String get name => child.name;

  /// 0 when not known (a child from the v1 sign-in).
  int get schoolId => child.school.id;
  String get schoolName => child.school.name;

  /// The avatar or photo URL; empty when the child has none.
  String get avatarUrl => child.image ?? '';

  Map<String, dynamic> toJson() => {
    ...child.toJson(),
    'student_number': ?studentNumber,
    'approved': ?approved,
    'homeroom': ?homeroom,
  };

  @override
  List<Object?> get props => [child, studentNumber, approved, homeroom];
}

/// A learner's guardian approval (`approval` in `/me/context`). Read by
/// hand until `openapi.yaml` describes it; then use the generated model.
class MeApproval extends Equatable {
  const MeApproval({required this.status, this.approvedByCount = 0});

  factory MeApproval.fromJson(Map<String, dynamic> json) => MeApproval(
    status: '${json['status'] ?? ''}',
    approvedByCount: json['approved_by_count'] is int
        ? json['approved_by_count'] as int
        : 0,
  );

  /// `pending`, `approved` or `withdrawn`.
  final String status;
  final int approvedByCount;

  bool get isApproved => status == 'approved';

  @override
  List<Object?> get props => [status, approvedByCount];
}

/// [value] decoded by [fromJson], or `null` when it is missing or does not
/// match the model.
T? _decode<T>(Object? value, T Function(Map<String, dynamic>) fromJson) {
  if (value is! Map) {
    return null;
  }
  try {
    return fromJson(Map<String, dynamic>.from(value));
  } catch (_) {
    return null;
  }
}

/// The items of [value] that decode; `null` when [value] is not a list.
List<T>? _decodeList<T>(
  Object? value,
  T Function(Map<String, dynamic>) fromJson,
) {
  if (value is! List) {
    return null;
  }
  return [
    for (final Object? item in value)
      if (_decode(item, fromJson) case final T decoded) decoded,
  ];
}

Map<String, bool> _flags(Object? value) => value is Map
    ? {
        for (final MapEntry<dynamic, dynamic> entry in value.entries)
          '${entry.key}': entry.value == true,
      }
    : const {};
