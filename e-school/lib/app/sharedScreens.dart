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
    if (context.watch<MeContextCubit>().context?.isModuleOn('wellbeing') ==
            true &&
        allowsSchoolModule(
          context.watch<MeContextCubit>().context,
          'wellbeing',
        ))
      tile(
        title: label('Report a concern'),
        icon: Icons.support_agent,
        onTap: () => open(Routes.reportConcern),
      ),
    if (context.watch<MeContextCubit>().context?.isFeatureOn('mastery') ==
            true &&
        allowsSchoolModule(context.watch<MeContextCubit>().context, 'mastery'))
      tile(
        title: label('Mastery map'),
        icon: Icons.account_tree_outlined,
        onTap: () => open(Routes.mastery),
      ),
    if (context.watch<MeContextCubit>().context?.isFeatureOn(
              'communication_messages',
            ) ==
            true &&
        allowsSchoolModule(
          context.watch<MeContextCubit>().context,
          'communication',
        ))
      tile(
        title: label('School messages'),
        icon: Icons.forum_outlined,
        onTap: () => open(Routes.schoolMessages),
      ),
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

Route<dynamic> schoolMessagesRoute(Object? arguments) => AdaptivePageRoute(
  contentColumn: false,
  builder: (context) {
    final session = context.read<MeContextCubit>();
    final schools = context.read<SchoolSelectionCubit>();
    final me = session.context;
    final school = me?.currentSchoolId, account = me?.user?.id;
    if (school == null ||
        account == null ||
        school <= 0 ||
        account <= 0 ||
        me?.isFeatureOn('communication_messages') != true ||
        !allowsSchoolModule(me, 'communication')) {
      return ScreenScaffold(
        title: SharedUiUtils.getTranslatedLabel(context, 'School messages'),
        body: Center(
          child: Text(
            SharedUiUtils.getTranslatedLabel(
              context,
              'Not available for this account',
            ),
          ),
        ),
      );
    }
    return ContentContextBoundary(
      schoolId: school,
      readerId: account,
      child: SchoolMessagesScreen(
        repository: ApiSchoolMessageRepository(
          Api.client,
          schoolId: school,
          readerId: account,
          contextActive: () =>
              session.context?.user?.id == account &&
              session.context?.currentSchoolId == school &&
              (schools.state.selectedId == null ||
                  schools.state.selectedId == school) &&
              session.context?.isFeatureOn('communication_messages') == true &&
              allowsSchoolModule(session.context, 'communication'),
        ),
        initialMessage: arguments is MessageLinkTarget ? arguments.id : null,
      ),
    );
  },
);

class SchoolMessagesEntry extends StatelessWidget {
  const SchoolMessagesEntry({super.key});
  @override
  Widget build(BuildContext context) =>
      BlocBuilder<MeContextCubit, MeContextState>(
        builder: (context, state) {
          if (state.context?.isFeatureOn('communication_messages') != true ||
              !allowsSchoolModule(state.context, 'communication'))
            return const SizedBox.shrink();
          return Card(
            child: ListTile(
              leading: const Icon(Icons.forum_outlined),
              title: Text(
                SharedUiUtils.getTranslatedLabel(context, 'School messages'),
              ),
              trailing: const Icon(Icons.chevron_right),
              onTap: () =>
                  Navigator.of(context).pushNamed(Routes.schoolMessages),
            ),
          );
        },
      );
}

Route<dynamic> activityGroupsRoute() => AdaptivePageRoute(
  contentColumn: false,
  builder: (context) {
    final session = context.read<MeContextCubit>();
    final schools = context.read<SchoolSelectionCubit>();
    final me = session.context;
    final school = me?.currentSchoolId, account = me?.user?.id;
    final children = context.read<ChildSelectionCubit>();
    final selectedChild = children.state.selectedId;
    if (school == null ||
        account == null ||
        !allowsSchoolModule(me, 'groups')) {
      return ScreenScaffold(
        title: SharedUiUtils.getTranslatedLabel(context, 'Activity groups'),
        body: Center(
          child: Text(
            SharedUiUtils.getTranslatedLabel(
              context,
              'Not available for this account',
            ),
          ),
        ),
      );
    }
    final pinned = me!;
    bool active() =>
        session.state is MeContextLoaded &&
        activityAccessStillCurrent(pinned, session.context) &&
        (schools.state.selectedId == null ||
            schools.state.selectedId == school) &&
        allowsSchoolModule(session.context, 'groups') &&
        children.state.selectedId == selectedChild;
    Widget result = ContentContextBoundary(
      schoolId: school,
      readerId: account,
      child: ActivitySessionBoundary(
        pinned: pinned,
        builder: (context, generationActive) => ActivityGroupsScreen(
          repository: ApiActivityRepository(
            Api.client,
            schoolId: school,
            accountId: account,
            contextActive: () => generationActive() && active(),
          ),
        ),
      ),
    );
    result = BlocListener<MeContextCubit, MeContextState>(
      listener: (context, state) {
        if (!active()) Navigator.of(context).popUntil((r) => r.isFirst);
      },
      child: result,
    );
    result = BlocListener<ChildSelectionCubit, ChildSelectionState>(
      listenWhen: (a, b) => a.selectedId != b.selectedId,
      listener: (context, state) {
        if (state.selectedId != selectedChild)
          Navigator.of(context).popUntil((r) => r.isFirst);
      },
      child: result,
    );
    return result;
  },
);

class ActivityGroupsEntry extends StatelessWidget {
  const ActivityGroupsEntry({super.key});
  @override
  Widget build(BuildContext context) =>
      BlocBuilder<MeContextCubit, MeContextState>(
        builder: (context, state) {
          if (!allowsSchoolModule(state.context, 'groups'))
            return const SizedBox.shrink();
          return Card(
            child: ListTile(
              leading: const Icon(Icons.groups_outlined),
              title: Text(
                SharedUiUtils.getTranslatedLabel(context, 'Activity groups'),
              ),
              trailing: const Icon(Icons.chevron_right),
              onTap: () =>
                  Navigator.of(context).pushNamed(Routes.activityGroups),
            ),
          );
        },
      );
}

Route<dynamic> masteryRoute() => AdaptivePageRoute(
  contentColumn: false,
  builder: (context) {
    final session = context.read<MeContextCubit>();
    final schools = context.read<SchoolSelectionCubit>();
    final me = session.context;
    final auth = context.read<AuthCubit>();
    final children = context.read<ChildSelectionCubit>();
    final parent = auth.isParent();
    final child = parent ? children.selected : null;
    final learner = parent ? child?.id : auth.getStudentDetails().id;
    final school = parent ? child?.schoolId : me?.currentSchoolId;
    final account = me?.user?.id;
    if (me == null ||
        school == null ||
        school <= 0 ||
        account == null ||
        account <= 0 ||
        learner == null ||
        learner <= 0 ||
        me.currentSchoolId != school ||
        (schools.state.selectedId != null &&
            schools.state.selectedId != school) ||
        me.isFeatureOn('mastery') != true ||
        !allowsSchoolModule(me, 'mastery')) {
      return ScreenScaffold(
        title: SharedUiUtils.getTranslatedLabel(context, 'Mastery map'),
        body: Center(
          child: Text(
            SharedUiUtils.getTranslatedLabel(
              context,
              'Not available for this account',
            ),
          ),
        ),
      );
    }
    return ContentContextBoundary(
      schoolId: school,
      readerId: account,
      learnerId: parent ? learner : null,
      child: MasterySessionBoundary(
        pinned: me,
        child: MasteryScreen(
          learner: learner,
          learnerName:
              child?.child.name ?? auth.getStudentDetails().getFullName(),
          repository: ApiMasteryRepository(
            Api.client,
            schoolId: school,
            readerId: account,
            contextActive: () =>
                masteryAccessStillCurrent(me, session.context) &&
                session.context?.user?.id == account &&
                session.context?.currentSchoolId == school &&
                (schools.state.selectedId == null ||
                    schools.state.selectedId == school) &&
                (!parent ||
                    children.selected?.id == learner &&
                        children.selected?.schoolId == school) &&
                session.context?.isFeatureOn('mastery') == true &&
                allowsSchoolModule(session.context, 'mastery'),
          ),
        ),
      ),
    );
  },
);

class MasteryEntry extends StatelessWidget {
  const MasteryEntry({super.key});
  @override
  Widget build(BuildContext context) =>
      BlocBuilder<MeContextCubit, MeContextState>(
        builder: (context, state) {
          if (state.context?.isFeatureOn('mastery') != true ||
              !allowsSchoolModule(state.context, 'mastery')) {
            return const SizedBox.shrink();
          }
          return Card(
            child: ListTile(
              leading: const Icon(Icons.account_tree_outlined),
              title: Text(
                SharedUiUtils.getTranslatedLabel(context, 'Mastery map'),
              ),
              trailing: const Icon(Icons.chevron_right),
              onTap: () => Navigator.of(context).pushNamed(Routes.mastery),
            ),
          );
        },
      );
}

Route<dynamic> familyProgressRoute() => AdaptivePageRoute(
  contentColumn: false,
  builder: (context) {
    final session = context.read<MeContextCubit>();
    final schools = context.read<SchoolSelectionCubit>();
    final me = session.context;
    final auth = context.read<AuthCubit>();
    final children = context.read<ChildSelectionCubit>();
    final parent = auth.isParent();
    final child = parent ? children.selected : null;
    final learner = parent ? child?.id : auth.getStudentDetails().id;
    final school = parent ? child?.schoolId : me?.currentSchoolId;
    final account = me?.user?.id;
    if (me == null ||
        school == null ||
        school <= 0 ||
        account == null ||
        account <= 0 ||
        learner == null ||
        learner <= 0 ||
        me.currentSchoolId != school ||
        (schools.state.selectedId != null &&
            schools.state.selectedId != school) ||
        !allowsSchoolModule(me, 'academics')) {
      return ScreenScaffold(
        title: SharedUiUtils.getTranslatedLabel(context, 'Learning progress'),
        body: Center(
          child: Text(
            SharedUiUtils.getTranslatedLabel(
              context,
              'Not available for this account',
            ),
          ),
        ),
      );
    }
    return ContentContextBoundary(
      schoolId: school,
      readerId: account,
      learnerId: parent ? learner : null,
      child: ProgressSessionBoundary(
        pinned: me,
        child: FamilyProgressScreen(
          learnerId: learner,
          canRead: () =>
              progressAccessStillCurrent(me, session.context) &&
              (!parent ||
                  children.selected?.id == learner &&
                      children.selected?.schoolId == school),
          repository: ApiProgressRepository(
            Api.client,
            schoolId: school,
            readerId: account,
            contextActive: () =>
                progressAccessStillCurrent(me, session.context) &&
                session.context?.user?.id == account &&
                session.context?.currentSchoolId == school &&
                (schools.state.selectedId == null ||
                    schools.state.selectedId == school) &&
                (!parent ||
                    children.selected?.id == learner &&
                        children.selected?.schoolId == school) &&
                allowsSchoolModule(session.context, 'academics'),
          ),
        ),
      ),
    );
  },
);

class FamilyProgressEntry extends StatelessWidget {
  const FamilyProgressEntry({super.key});
  @override
  Widget build(BuildContext context) =>
      BlocBuilder<MeContextCubit, MeContextState>(
        builder: (context, state) {
          if (!allowsSchoolModule(state.context, 'academics'))
            return const SizedBox.shrink();
          return Card(
            child: ListTile(
              leading: const Icon(Icons.insights_outlined),
              title: Text(
                SharedUiUtils.getTranslatedLabel(context, 'Learning progress'),
              ),
              trailing: const Icon(Icons.chevron_right),
              onTap: () =>
                  Navigator.of(context).pushNamed(Routes.familyProgress),
            ),
          );
        },
      );
}

Route<dynamic> reportConcernRoute() => AdaptivePageRoute(
  contentColumn: false,
  builder: (context) {
    final session = context.read<MeContextCubit>(),
        schools = context.read<SchoolSelectionCubit>();
    final auth = context.read<AuthCubit>(),
        children = context.read<ChildSelectionCubit>();
    final me = session.context,
        school = me?.currentSchoolId,
        account = me?.user?.id;
    final parent = auth.isParent(),
        child = auth.isParent() ? children.selected : null;
    final learner = parent ? child?.id : auth.getStudentDetails().id;
    if (me == null ||
        school == null ||
        account == null ||
        account <= 0 ||
        school <= 0 ||
        !allowsSchoolModule(me, 'wellbeing') ||
        !me.isModuleOn('wellbeing') ||
        (child != null && child.schoolId != school)) {
      return ScreenScaffold(
        title: SharedUiUtils.getTranslatedLabel(context, 'Report a concern'),
        body: Center(
          child: Text(
            SharedUiUtils.getTranslatedLabel(
              context,
              'Not available for this account',
            ),
          ),
        ),
      );
    }
    return ContentContextBoundary(
      schoolId: school,
      readerId: account,
      learnerId: parent ? learner : null,
      child: ReportingSessionBoundary(
        pinned: me,
        builder: (_, active) => IncidentReportingScreen(
          reporterName: me.user!.name,
          learnerId: learner,
          learnerName: parent
              ? child?.child.name
              : auth.getStudentDetails().getFullName(),
          repository: ApiReportingRepository(
            Api.client,
            schoolId: school,
            accountId: account,
            contextActive: () =>
                active() &&
                (schools.state.selectedId == null ||
                    schools.state.selectedId == school) &&
                (!parent || children.selected?.id == learner),
          ),
        ),
      ),
    );
  },
);

class ReportConcernEntry extends StatelessWidget {
  const ReportConcernEntry({super.key});
  @override
  Widget build(BuildContext context) =>
      BlocBuilder<MeContextCubit, MeContextState>(
        builder: (context, state) {
          if (!allowsSchoolModule(state.context, 'wellbeing') ||
              state.context?.isModuleOn('wellbeing') != true)
            return const SizedBox.shrink();
          return Card(
            child: ListTile(
              leading: const Icon(Icons.support_agent),
              title: Text(
                SharedUiUtils.getTranslatedLabel(context, 'Report a concern'),
              ),
              trailing: const Icon(Icons.chevron_right),
              onTap: () =>
                  Navigator.of(context).pushNamed(Routes.reportConcern),
            ),
          );
        },
      );
}

Route<dynamic> schoolTransfersRoute() => AdaptivePageRoute(
  contentColumn: false,
  builder: (context) {
    final session = context.read<MeContextCubit>();
    final schools = context.read<SchoolSelectionCubit>();
    final pinned = session.context;
    final school = pinned?.currentSchoolId;
    final account = pinned?.user?.id;
    if (!context.read<AuthCubit>().isParent() ||
        pinned == null ||
        school == null ||
        school <= 0 ||
        account == null ||
        account <= 0 ||
        !pinned.isModuleOn('school_transfers') ||
        !allowsSchoolModule(pinned, 'school_transfers') ||
        (schools.state.selectedId != null &&
            schools.state.selectedId != school)) {
      return ScreenScaffold(
        title: SharedUiUtils.getTranslatedLabel(context, 'School transfers'),
        body: Center(
          child: Text(
            SharedUiUtils.getTranslatedLabel(
              context,
              'Not available for this account',
            ),
          ),
        ),
      );
    }
    return TransferSessionBoundary(
      pinned: pinned,
      builder: (active) => TransferConsentScreen(
        repository: ApiTransferRepository(
          Api.client,
          schoolId: school,
          accountId: account,
          contextActive: () =>
              active() &&
              (schools.state.selectedId == null ||
                  schools.state.selectedId == school),
        ),
      ),
    );
  },
);

class SchoolTransfersEntry extends StatelessWidget {
  const SchoolTransfersEntry({super.key});
  @override
  Widget build(BuildContext context) =>
      BlocBuilder<MeContextCubit, MeContextState>(
        builder: (context, state) {
          if (!context.read<AuthCubit>().isParent() ||
              state.context?.isModuleOn('school_transfers') != true ||
              !allowsSchoolModule(state.context, 'school_transfers')) {
            return const SizedBox.shrink();
          }
          return Card(
            child: ListTile(
              leading: const Icon(Icons.transfer_within_a_station),
              title: Text(
                SharedUiUtils.getTranslatedLabel(context, 'School transfers'),
              ),
              trailing: const Icon(Icons.chevron_right),
              onTap: () =>
                  Navigator.of(context).pushNamed(Routes.schoolTransfers),
            ),
          );
        },
      );
}

Route<dynamic> familyDisciplineRoute() => AdaptivePageRoute(
  contentColumn: false,
  builder: (context) {
    final session = context.read<MeContextCubit>();
    final schools = context.read<SchoolSelectionCubit>();
    final me = session.context;
    final auth = context.read<AuthCubit>();
    final children = context.read<ChildSelectionCubit>();
    final parent = auth.isParent();
    final child = parent ? children.selected : null;
    final learner = parent ? child?.id : auth.getStudentDetails().id;
    final school = parent ? child?.schoolId : me?.currentSchoolId;
    final account = me?.user?.id;
    if (me == null ||
        school == null ||
        school <= 0 ||
        account == null ||
        account <= 0 ||
        learner == null ||
        learner <= 0 ||
        me.currentSchoolId != school ||
        (schools.state.selectedId != null &&
            schools.state.selectedId != school) ||
        !allowsSchoolModule(me, 'discipline')) {
      return ScreenScaffold(
        title: SharedUiUtils.getTranslatedLabel(context, 'School discipline'),
        body: Center(
          child: Text(
            SharedUiUtils.getTranslatedLabel(
              context,
              'Not available for this account',
            ),
          ),
        ),
      );
    }
    return ContentContextBoundary(
      schoolId: school,
      readerId: account,
      learnerId: parent ? learner : null,
      child: DisciplineSessionBoundary(
        pinned: me,
        child: FamilyDisciplineScreen(
          learnerId: learner,
          guardian: parent,
          canWrite: () =>
              allowsSchoolModule(session.context, 'discipline', write: true),
          canRead: () =>
              disciplineAccessStillCurrent(me, session.context) &&
              (!parent ||
                  children.selected?.id == learner &&
                      children.selected?.schoolId == school),
          repository: ApiDisciplineRepository(
            Api.client,
            schoolId: school,
            readerId: account,
            contextActive: () =>
                disciplineAccessStillCurrent(me, session.context) &&
                session.context?.user?.id == account &&
                session.context?.currentSchoolId == school &&
                (schools.state.selectedId == null ||
                    schools.state.selectedId == school) &&
                (!parent ||
                    children.selected?.id == learner &&
                        children.selected?.schoolId == school) &&
                allowsSchoolModule(session.context, 'discipline'),
          ),
        ),
      ),
    );
  },
);

class FamilyDisciplineEntry extends StatelessWidget {
  const FamilyDisciplineEntry({super.key});
  @override
  Widget build(BuildContext context) =>
      BlocBuilder<MeContextCubit, MeContextState>(
        builder: (context, state) {
          if (!allowsSchoolModule(state.context, 'discipline'))
            return const SizedBox.shrink();
          return Card(
            child: ListTile(
              leading: const Icon(Icons.insights_outlined),
              title: Text(
                SharedUiUtils.getTranslatedLabel(context, 'School discipline'),
              ),
              trailing: const Icon(Icons.chevron_right),
              onTap: () =>
                  Navigator.of(context).pushNamed(Routes.familyDiscipline),
            ),
          );
        },
      );
}

Route<dynamic> familyRecognitionRoute() => AdaptivePageRoute(
  contentColumn: false,
  builder: (context) {
    final session = context.read<MeContextCubit>(),
        schools = context.read<SchoolSelectionCubit>();
    final me = session.context,
        auth = context.read<AuthCubit>(),
        children = context.read<ChildSelectionCubit>();
    final parent = auth.isParent(),
        child = auth.isParent() ? children.selected : null;
    final learner = parent ? child?.id : auth.getStudentDetails().id;
    final school = parent ? child?.schoolId : me?.currentSchoolId,
        account = me?.user?.id;
    final modules = [
      for (final key in ['house_points', 'awards'])
        if (me?.isModuleOn(key) == true && allowsSchoolModule(me, key)) key,
    ];
    if (me == null ||
        school == null ||
        school <= 0 ||
        account == null ||
        account <= 0 ||
        learner == null ||
        learner <= 0 ||
        me.currentSchoolId != school ||
        modules.isEmpty ||
        (schools.state.selectedId != null &&
            schools.state.selectedId != school)) {
      return ScreenScaffold(
        title: SharedUiUtils.getTranslatedLabel(context, 'Houses and awards'),
        body: Center(
          child: Text(
            SharedUiUtils.getTranslatedLabel(
              context,
              'Not available for this account',
            ),
          ),
        ),
      );
    }
    return ContentContextBoundary(
      schoolId: school,
      readerId: account,
      learnerId: parent ? learner : null,
      child: RecognitionSessionBoundary(
        pinned: me,
        modules: modules,
        builder: (active) => RecognitionScreen(
          repository: ApiRecognitionRepository(
            Api.client,
            schoolId: school,
            accountId: account,
            contextActive: () =>
                active() &&
                (schools.state.selectedId == null ||
                    schools.state.selectedId == school) &&
                (!parent ||
                    children.selected?.id == learner &&
                        children.selected?.schoolId == school),
          ),
          modules: modules,
          family: true,
          learnerId: learner,
          learnerName:
              child?.child.name ?? auth.getStudentDetails().getFullName(),
        ),
      ),
    );
  },
);

class FamilyRecognitionEntry extends StatelessWidget {
  const FamilyRecognitionEntry({super.key});
  @override
  Widget build(BuildContext context) =>
      BlocBuilder<MeContextCubit, MeContextState>(
        builder: (context, state) {
          if (!['house_points', 'awards'].any(
            (key) =>
                state.context?.isModuleOn(key) == true &&
                allowsSchoolModule(state.context, key),
          )) {
            return const SizedBox.shrink();
          }
          return Card(
            child: ListTile(
              leading: const Icon(Icons.emoji_events_outlined),
              title: Text(
                SharedUiUtils.getTranslatedLabel(context, 'Houses and awards'),
              ),
              trailing: const Icon(Icons.chevron_right),
              onTap: () => Navigator.of(context).pushNamed(Routes.recognition),
            ),
          );
        },
      );
}
