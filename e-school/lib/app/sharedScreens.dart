import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool/app/routes.dart';
import 'package:eschool/cubits/authCubit.dart';
import 'package:eschool/data/models/parent.dart';
import 'package:eschool/data/models/student.dart';
import 'package:eschool/data/repositories/settingsRepository.dart';
import 'package:eschool/utils/api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_notifications/eschool_notifications.dart';
import 'package:eschool_people/eschool_people.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

// The screens built once in the shared packages (eschool_notifications,
// eschool_people), as this app opens them: their routes, and the checks that
// keep their entry points hidden until the server has their endpoints.

Route<dynamic> notificationSettingsRoute() => AdaptivePageRoute(
  builder: (_) => NotificationSettingsScreen.withRepository(
    ApiNotificationSettingsRepository(Api.client),
  ),
);

Route<dynamic> approvalsRoute() => AdaptivePageRoute(
  builder: (context) => ApprovalsScreen.withRepository(
    ApiApprovalsRepository(Api.client),
    // the children's `approved` comes with /me/context
    onChanged: () => context.read<MeContextCubit>().load(),
  ),
);

Route<dynamic> mySchoolRoute() => AdaptivePageRoute(
  builder: (context) => MySchoolScreen.withRepository(
    ApiMySchoolRepository(Api.client),
    schools: mySchoolChoices(context),
  ),
);

Route<dynamic> invitationRoute(String token) => AdaptivePageRoute(
  builder: (_) => InvitationScreen.withRepository(
    ApiInvitationRepository(
      Api.client,
      deviceFields: NotificationUtility.signInDeviceFields,
    ),
    token: token,
    onAccepted: _signInFromInvitation,
  ),
);

Route<dynamic> profileDetailsRoute() => AdaptivePageRoute(
  builder: (context) {
    final me = context.read<MeContextCubit>().context;
    String label(String key) => SharedUiUtils.getTranslatedLabel(context, key);
    final subjects = <ProfileSubject>[
      if (me == null || me.schools.isEmpty) const ProfileSubject(),
      if (me != null)
        for (final membership in me.schools)
          ProfileSubject(
            schoolId: membership.school.id,
            label: '${label(profileMyDetailsKey)} — ${membership.school.name}',
          ),
      if (context.read<AuthCubit>().isParent())
        for (final child in context.read<ChildSelectionCubit>().state.children)
          if (child.userId > 0)
            ProfileSubject(
              personId: child.userId,
              schoolId: child.schoolId > 0 ? child.schoolId : null,
              profileType: 'learner',
              label: '${child.child.name} — ${child.child.school.name}',
            ),
    ];
    // The current school is first for the person's own details.
    final schoolId = me?.currentSchoolId;
    subjects.sort(
      (a, b) => (a.personId == null && a.schoolId == schoolId ? 0 : 1)
          .compareTo(b.personId == null && b.schoolId == schoolId ? 0 : 1),
    );
    return ProfileScreen.withRepository(
      ApiProfileRepository(Api.client),
      subjects: subjects,
      onSaved: () => context.read<MeContextCubit>().load(),
    );
  },
);

Route<dynamic> handlesRoute() => AdaptivePageRoute(
  builder: (context) => HandlesScreen.withRepository(
    ApiHandlesRepository(Api.client),
    guardian: context.read<AuthCubit>().isParent(),
    onChanged: () => context.read<MeContextCubit>().load(),
  ),
);

Route<dynamic> profilePictureRoute() => AdaptivePageRoute(
  builder: (context) {
    final AuthCubit auth = context.read<AuthCubit>();
    final bool isParent = auth.isParent();
    final Parent parent = auth.getParentDetails();
    final Student student = auth.getStudentDetails();
    return AvatarScreen.withRepository(
      ApiAvatarRepository(Api.client),
      name: isParent ? parent.getFullName() : student.getFullName(),
      isLearner: !isParent,
      gender: isParent ? parent.gender : null,
      seed: isParent ? parent.userId : student.userId,
      onSaved: () => context.read<MeContextCubit>().load(),
    );
  },
);

/// An accepted invitation answers like the parent sign-in: sign in the
/// same way, then the policies to accept, then home.
void _signInFromInvitation(BuildContext context, Map<String, dynamic> signIn) {
  final Object? data = signIn['data'];
  context.read<AuthCubit>().authenticateUser(
    jwtToken: '${signIn['token'] ?? ''}',
    isStudent: false,
    parent: Parent.fromJson(data is Map ? Map.from(data) : {}),
    student: Student.fromJson({}),
  );
  SettingsRepository().setNotificationCount(0);
  Navigator.of(context)
      .pushNamedAndRemoveUntil(Routes.parentPolicies, (_) => false);
}

/// The schools My school offers: a parent's children's schools (the child
/// on show's first), otherwise the school the user is working in.
List<SchoolSummary> mySchoolChoices(BuildContext context) {
  final List<SchoolSummary> schools = [];
  void add(SchoolSummary school) {
    if (school.id > 0 && !schools.any((s) => s.id == school.id)) {
      schools.add(school);
    }
  }

  if (context.read<AuthCubit>().isParent()) {
    final ChildSelectionState children = context
        .read<ChildSelectionCubit>()
        .state;
    final MeChild? shown = children.selected;
    if (shown != null) {
      add(shown.child.school);
    }
    for (final MeChild child in children.children) {
      add(child.child.school);
    }
  }
  final CurrentSchool? current = context
      .read<MeContextCubit>()
      .context
      ?.currentSchool;
  if (schools.isEmpty && current != null) {
    add(
      SchoolSummary(
        id: current.id,
        name: current.name,
        shortName: current.shortName,
        crestUrl: current.crestUrl,
      ),
    );
  }
  return schools;
}

/// The entry points of the shared screens in Settings, each hidden until
/// the server has its endpoint. [tile] draws a row the way Settings does.
List<Widget> sharedScreenEntries(
  BuildContext context, {
  required Widget Function({
    required String title,
    required IconData icon,
    required VoidCallback onTap,
  })
  tile,
}) {
  final bool isParent = context.read<AuthCubit>().isParent();
  String label(String key) => SharedUiUtils.getTranslatedLabel(context, key);
  void open(String route) => Navigator.of(context).pushNamed(route);
  return [
    if (context.watch<MeContextCubit>().context?.isFeatureOn(
          'academic_content',
        ) ==
        true)
      tile(
        title: label('Learning content'),
        icon: Icons.menu_book_outlined,
        onTap: () => open(Routes.academicContent),
      ),
    if (context.watch<MeContextCubit>().context?.isFeatureOn(
          'academic_reports',
        ) ==
        true)
      tile(
        title: label('reportCards'),
        icon: Icons.description_outlined,
        onTap: () => open(Routes.academicReports),
      ),
    if (context.watch<MeContextCubit>().context?.isFeatureOn(
          'academic_registers',
        ) ==
        true)
      tile(
        title: label('attendanceAndQr'),
        icon: Icons.fact_check_outlined,
        onTap: () => open(Routes.academicAttendance),
      ),
    if (context.watch<MeContextCubit>().context?.isFeatureOn(
          'academic_timetable',
        ) ==
        true)
      tile(
        title: label('academicTimetable'),
        icon: Icons.calendar_view_week_outlined,
        onTap: () => open(Routes.academicTimetable),
      ),
    if (context.watch<MeContextCubit>().context?.isFeatureOn(
          'academic_marks',
        ) ==
        true)
      tile(
        title: label('assessmentMarks'),
        icon: Icons.grid_on_outlined,
        onTap: () => open(Routes.academicMarks),
      ),
    AvailableOnly(
      featureKey: profileFieldsFeature,
      probe: () => profilesDeployed(ApiProfileRepository(Api.client)),
      child: tile(
        title: label(profileDetailsKey),
        icon: Icons.person_outline,
        onTap: () => open(Routes.profileDetails),
      ),
    ),
    AvailableOnly(
      featureKey: handlesFeature,
      probe: () => handlesDeployed(ApiHandlesRepository(Api.client)),
      child: tile(
        title: label(handleSettingsKey),
        icon: Icons.alternate_email,
        onTap: () => open(Routes.handles),
      ),
    ),
    AvailableOnly(
      featureKey: notificationSettingsFeature,
      probe: () => notificationSettingsDeployed(
        ApiNotificationSettingsRepository(Api.client),
      ),
      child: tile(
        title: label(notificationSettingsKey),
        icon: Icons.tune,
        onTap: () => open(Routes.notificationSettings),
      ),
    ),
    AvailableOnly(
      featureKey: approvalsFeature,
      enabled: isParent,
      probe: () => approvalsDeployed(ApiApprovalsRepository(Api.client)),
      child: tile(
        title: label(approvalsKey),
        icon: Icons.verified_user_outlined,
        onTap: () => open(Routes.approvals),
      ),
    ),
    AvailableOnly(
      featureKey: mySchoolFeature,
      probe: () async {
        final List<SchoolSummary> schools = mySchoolChoices(context);
        return schools.isNotEmpty &&
            await mySchoolDeployed(
              ApiMySchoolRepository(Api.client),
              schools.first.id,
            );
      },
      child: tile(
        title: label(mySchoolKey),
        icon: Icons.school_outlined,
        onTap: () => open(Routes.mySchool),
      ),
    ),
    AvailableOnly(
      featureKey: avatarFeature,
      probe: () => avatarsDeployed(ApiAvatarRepository(Api.client)),
      child: tile(
        title: label(profilePictureKey),
        icon: Icons.face_outlined,
        onTap: () => open(Routes.profilePicture),
      ),
    ),
  ];
}

Route<dynamic> academicMarksRoute() => AdaptivePageRoute(
  builder: (context) {
    final me = context.read<MeContextCubit>().context;
    final auth = context.read<AuthCubit>();
    final child = auth.isParent()
        ? context.read<ChildSelectionCubit>().selected
        : null;
    final learner = auth.isParent() ? child?.id : auth.getStudentDetails().id;
    final school = auth.isParent() ? child?.schoolId : me?.currentSchoolId;
    if (learner == null ||
        learner <= 0 ||
        school == null ||
        school <= 0 ||
        me?.isFeatureOn('academic_marks') != true) {
      return ScreenScaffold(
        title: SharedUiUtils.getTranslatedLabel(context, 'assessmentMarks'),
        body: Center(
          child: Text(
            SharedUiUtils.getTranslatedLabel(context, 'notAvailableYet'),
          ),
        ),
      );
    }
    return AcademicContextBoundary(
      schoolId: context.read<SchoolSelectionCubit>().state.selectedId ?? school,
      childId: auth.isParent() ? learner : null,
      child: ReleasedMarksScreen(
        repository: ApiMarksRepository(Api.client, schoolId: school),
        learner: learner,
        learnerName:
            child?.child.name ?? auth.getStudentDetails().getFullName(),
      ),
    );
  },
);

Route<dynamic> academicTimetableRoute() => AdaptivePageRoute(
  builder: (context) {
    final me = context.read<MeContextCubit>().context;
    final auth = context.read<AuthCubit>();
    final child = auth.isParent()
        ? context.read<ChildSelectionCubit>().selected
        : null;
    final learner = auth.isParent() ? child?.id : auth.getStudentDetails().id;
    final school = auth.isParent() ? child?.schoolId : me?.currentSchoolId;
    if (learner == null ||
        learner <= 0 ||
        school == null ||
        school <= 0 ||
        me?.isFeatureOn('academic_timetable') != true) {
      return const ScreenScaffold(
        title: 'Academic timetable',
        body: Center(child: Text('Not available yet')),
      );
    }
    return AcademicContextBoundary(
      schoolId: context.read<SchoolSelectionCubit>().state.selectedId ?? school,
      childId: auth.isParent() ? learner : null,
      child: TimetableScreen.withRepository(
        ApiTimetableRepository(Api.client, schoolId: school),
        learner: learner,
        title: child?.child.name ?? auth.getStudentDetails().getFullName(),
      ),
    );
  },
);

class AcademicNextPeriod extends StatelessWidget {
  const AcademicNextPeriod({super.key});
  @override
  Widget build(BuildContext context) {
    final me = context.watch<MeContextCubit>().context;
    final auth = context.read<AuthCubit>();
    final child = auth.isParent()
        ? context.watch<ChildSelectionCubit>().selected
        : null;
    final learner = auth.isParent() ? child?.id : auth.getStudentDetails().id;
    final school = auth.isParent() ? child?.schoolId : me?.currentSchoolId;
    if (learner == null ||
        learner <= 0 ||
        school == null ||
        school <= 0 ||
        me?.isFeatureOn('academic_timetable') != true)
      return const SizedBox.shrink();
    final scope = 'family:$school:$learner';
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: NextPeriodCard(
        key: ValueKey(scope),
        scope: scope,
        learner: learner,
        learnerName: child?.child.name,
        repository: ApiTimetableRepository(Api.client, schoolId: school),
        onOpen: () => Navigator.of(context).pushNamed(Routes.academicTimetable),
      ),
    );
  }
}

Route<dynamic> academicAttendanceRoute() => AdaptivePageRoute(
  builder: (context) {
    final me = context.read<MeContextCubit>().context;
    final auth = context.read<AuthCubit>();
    final child = auth.isParent()
        ? context.read<ChildSelectionCubit>().selected
        : null;
    final learner = auth.isParent() ? child?.id : auth.getStudentDetails().id;
    final school = auth.isParent() ? child?.schoolId : me?.currentSchoolId;
    if (learner == null ||
        learner <= 0 ||
        school == null ||
        school <= 0 ||
        me?.isFeatureOn('academic_registers') != true) {
      return const ScreenScaffold(
        title: 'Attendance',
        body: Center(child: Text('Not available yet')),
      );
    }
    return AcademicContextBoundary(
      schoolId: context.read<SchoolSelectionCubit>().state.selectedId ?? school,
      childId: auth.isParent() ? learner : null,
      child: FamilyAttendanceScreen(
        repository: ApiRegisterRepository(Api.client, schoolId: school),
        learner: learner,
        learnerName:
            child?.child.name ?? auth.getStudentDetails().getFullName(),
      ),
    );
  },
);

class AcademicTodayAttendance extends StatelessWidget {
  const AcademicTodayAttendance({super.key});
  @override
  Widget build(BuildContext context) {
    final me = context.watch<MeContextCubit>().context;
    final auth = context.read<AuthCubit>();
    final child = auth.isParent()
        ? context.watch<ChildSelectionCubit>().selected
        : null;
    final learner = auth.isParent() ? child?.id : auth.getStudentDetails().id;
    final school = auth.isParent() ? child?.schoolId : me?.currentSchoolId;
    if (learner == null ||
        learner <= 0 ||
        school == null ||
        school <= 0 ||
        me?.isFeatureOn('academic_registers') != true)
      return const SizedBox.shrink();
    final scope = 'family:$school:$learner';
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: TodayAttendanceCard(
        key: ValueKey(scope),
        scope: scope,
        learner: learner,
        learnerName: child?.child.name,
        repository: ApiRegisterRepository(Api.client, schoolId: school),
        onOpen: () =>
            Navigator.of(context).pushNamed(Routes.academicAttendance),
      ),
    );
  }
}

Route<dynamic> academicReportsRoute() => AdaptivePageRoute(
  builder: (context) {
    final me = context.read<MeContextCubit>().context;
    final auth = context.read<AuthCubit>();
    final child = auth.isParent()
        ? context.read<ChildSelectionCubit>().selected
        : null;
    final learner = auth.isParent() ? child?.id : auth.getStudentDetails().id;
    final school = auth.isParent() ? child?.schoolId : me?.currentSchoolId;
    if (learner == null ||
        learner <= 0 ||
        school == null ||
        school <= 0 ||
        me?.isFeatureOn('academic_reports') != true) {
      return const ScreenScaffold(
        title: 'Report cards',
        body: Center(child: Text('Not available yet')),
      );
    }
    return AcademicContextBoundary(
      schoolId: context.read<SchoolSelectionCubit>().state.selectedId ?? school,
      childId: auth.isParent() ? learner : null,
      child: FamilyReportsScreen(
        repository: ApiReportRepository(Api.client, schoolId: school),
        learner: learner,
        learnerName:
            child?.child.name ?? auth.getStudentDetails().getFullName(),
      ),
    );
  },
);

class AcademicReportsEntry extends StatelessWidget {
  const AcademicReportsEntry({super.key});
  @override
  Widget build(BuildContext context) {
    if (context.watch<MeContextCubit>().context?.isFeatureOn(
          'academic_reports',
        ) !=
        true)
      return const SizedBox.shrink();
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: Card(
        child: ListTile(
          leading: const Icon(Icons.description_outlined),
          title: Text(SharedUiUtils.getTranslatedLabel(context, 'reportCards')),
          trailing: const Icon(Icons.chevron_right),
          onTap: () => Navigator.of(context).pushNamed(Routes.academicReports),
        ),
      ),
    );
  }
}

Route<dynamic> academicContentRoute(Object? arguments) => AdaptivePageRoute(
  builder: (context) => _FamilyContentRoute(
    target: arguments is ContentLinkTarget ? arguments : null,
  ),
);

class _FamilyContentRoute extends StatefulWidget {
  const _FamilyContentRoute({this.target});
  final ContentLinkTarget? target;
  @override
  State<_FamilyContentRoute> createState() => _FamilyContentRouteState();
}

class _FamilyContentRouteState extends State<_FamilyContentRoute> {
  bool _ready = false, _allowed = true;
  @override
  void initState() {
    super.initState();
    _prepare();
  }

  Future<void> _prepare() async {
    final auth = context.read<AuthCubit>();
    final requested = widget.target?.learner;
    try {
      if (requested != null) {
        if (auth.isParent()) {
          final child = await context.read<ChildSelectionCubit>().select(
            requested,
          );
          _allowed = child != null;
        } else {
          _allowed = requested == auth.getStudentDetails().id;
        }
      }
    } catch (_) {
      _allowed = false;
    }
    if (mounted) setState(() => _ready = true);
  }

  @override
  Widget build(BuildContext context) {
    if (!_ready)
      return const ScreenScaffold(
        title: 'Learning content',
        body: Center(child: CircularProgressIndicator()),
      );
    final session = context.read<MeContextCubit>();
    final schools = context.read<SchoolSelectionCubit>();
    final children = context.read<ChildSelectionCubit>();
    final me = session.context;
    final auth = context.read<AuthCubit>();
    final parent = auth.isParent();
    final child = parent ? children.selected : null;
    final learner = parent ? child?.id : auth.getStudentDetails().id;
    final school = parent ? child?.schoolId : me?.currentSchoolId;
    final account = me?.user?.id;
    if (!_allowed ||
        learner == null ||
        learner <= 0 ||
        school == null ||
        school <= 0 ||
        account == null ||
        account <= 0 ||
        me?.isFeatureOn('academic_content') != true) {
      return const ScreenScaffold(
        title: 'Learning content',
        body: Center(child: Text('Not available for this account')),
      );
    }
    final selectedSchool = schools.state.selectedId;
    final repository = ApiAcademicContentRepository(
      Api.client,
      schoolId: school,
      readerId: account,
      contextActive: () =>
          session.context?.user?.id == account &&
          schools.state.selectedId == selectedSchool &&
          (!parent || children.state.selectedId == learner),
    );
    late ContentReaderCache cache;
    cache = ContentReaderCache(
      repository,
      store: () => SecureContentStore.open(
        account,
        school,
        active: () => cache.isActive,
      ),
    );
    return ContentContextBoundary(
      schoolId: selectedSchool ?? school,
      readerId: account,
      learnerId: parent ? learner : null,
      child: LearningContentScreen(
        repository: repository,
        learner: learner,
        learnerName:
            child?.child.name ?? auth.getStudentDetails().getFullName(),
        initialVersion: widget.target?.version,
        cache: cache,
      ),
    );
  }
}

class AcademicContentEntry extends StatelessWidget {
  const AcademicContentEntry({super.key});
  @override
  Widget build(BuildContext context) {
    if (context.watch<MeContextCubit>().context?.isFeatureOn(
          'academic_content',
        ) !=
        true)
      return const SizedBox.shrink();
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: Card(
        child: ListTile(
          leading: const Icon(Icons.menu_book_outlined),
          title: Text(contentLabel(context, 'Learning content')),
          trailing: const Icon(Icons.chevron_right),
          onTap: () => Navigator.of(context).pushNamed(Routes.academicContent),
        ),
      ),
    );
  }
}
