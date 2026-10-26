import 'package:eschool_teacher/utils/teachingCamera.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_notifications/eschool_notifications.dart';
import 'package:eschool_people/eschool_people.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:eschool_teacher/app/routes.dart';
import 'package:eschool_teacher/cubits/authCubit.dart';
import 'package:eschool_teacher/data/models/teacher.dart';
import 'package:eschool_teacher/utils/api.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

// The screens built once in the shared packages (eschool_notifications,
// eschool_people) that a teacher uses, as this app opens them: their routes,
// and the checks that keep their entry points hidden until the server has
// their endpoints. Approvals and invitations are a guardian's, in the
// learner and parent app.

Route<dynamic> notificationSettingsRoute() => AdaptivePageRoute(
  builder: (_) => NotificationSettingsScreen.withRepository(
    ApiNotificationSettingsRepository(Api.client),
  ),
);

Route<dynamic> mySchoolRoute() => AdaptivePageRoute(
  builder: (context) => MySchoolScreen.withRepository(
    ApiMySchoolRepository(Api.client),
    schools: mySchoolChoices(context),
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
    guardian: false,
    onChanged: () => context.read<MeContextCubit>().load(),
  ),
);

Route<dynamic> profilePictureRoute() => AdaptivePageRoute(
  builder: (context) {
    final Teacher teacher = context.read<AuthCubit>().getTeacherDetails();
    return AvatarScreen.withRepository(
      ApiAvatarRepository(Api.client),
      name: teacher.getFullName(),
      isLearner: false,
      gender: teacher.gender,
      seed: teacher.userId,
      onSaved: () => context.read<MeContextCubit>().load(),
    );
  },
);

/// The school My school shows: the one the teacher is working in.
List<SchoolSummary> mySchoolChoices(BuildContext context) {
  final CurrentSchool? current = context
      .read<MeContextCubit>()
      .context
      ?.currentSchool;
  return [
    if (current != null && current.id > 0)
      SchoolSummary(
        id: current.id,
        name: current.name,
        shortName: current.shortName,
        crestUrl: current.crestUrl,
      ),
  ];
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
  String label(String key) => SharedUiUtils.getTranslatedLabel(context, key);
  void open(String route) => Navigator.of(context).pushNamed(route);
  return [
    for (final entry in <(String, String, String)>[
      ('teaching_assistant', Routes.teachingAssistant, 'Teaching assistant'),
      ('test_insights', Routes.testInsights, 'Test insights'),
    ])
      if (context.watch<MeContextCubit>().context?.featureFlags[entry.$1] ==
              true &&
          allowsSchoolModule(context.watch<MeContextCubit>().context, entry.$1))
        tile(
          title: label(entry.$3),
          icon: Icons.auto_awesome_outlined,
          onTap: () => open(entry.$2),
        ),
    if (context.watch<MeContextCubit>().context?.featureFlags['my_grade'] ==
            true &&
        allowsSchoolModule(context.watch<MeContextCubit>().context, 'my_grade'))
      tile(
        title: label('My grade'),
        icon: Icons.groups_outlined,
        onTap: () => open(Routes.myGrade),
      ),
    AvailableOnly(
      featureKey:
          'secure-cards:${context.watch<MeContextCubit>().context?.currentSchoolId}:${context.watch<MeContextCubit>().context?.user?.id}:${context.watch<MeContextCubit>().context?.roles.join(',')}',
      enabled:
          context.watch<MeContextCubit>().context?.isModuleOn('cards') ==
              true &&
          allowsSchoolModule(context.watch<MeContextCubit>().context, 'cards'),
      probe: () async {
        final session = context.read<MeContextCubit>();
        final pinned = session.context;
        if (pinned?.currentSchoolId == null ||
            pinned?.user?.id == null ||
            session.state is! MeContextLoaded)
          return false;
        return cardsDeployed(
          ApiCardRepository(
            Api.client,
            schoolId: pinned!.currentSchoolId!,
            accountId: pinned.user!.id,
            contextActive: () =>
                session.state is MeContextLoaded &&
                cardAccessStillCurrent(pinned, session.context),
          ),
        );
      },
      child: tile(
        title: label('School identity cards'),
        icon: Icons.badge_outlined,
        onTap: () => open(Routes.schoolCards),
      ),
    ),
    if (context.watch<MeContextCubit>().context?.isModuleOn('daily_practice') ==
            true &&
        context.watch<MeContextCubit>().context?.roles.contains('Teacher') ==
            true)
      tile(
        title: label('Practice question bank'),
        icon: Icons.psychology_alt_outlined,
        onTap: () => open(Routes.dailyPractice),
      ),
    if (allowsSchoolModule(
      context.watch<MeContextCubit>().context,
      'discipline',
    ))
      tile(
        title: label('School discipline'),
        icon: Icons.fact_check_outlined,
        onTap: () => open(Routes.schoolDiscipline),
      ),
    if (context.watch<MeContextCubit>().context?.isModuleOn('early_warning') ==
            true &&
        allowsSchoolModule(
          context.watch<MeContextCubit>().context,
          'early_warning',
        ))
      tile(
        title: label('Staff follow-up'),
        icon: Icons.assignment_ind_outlined,
        onTap: () => open(Routes.riskInterventions),
      ),
    if (allowsSchoolModule(
      context.watch<MeContextCubit>().context,
      'academics',
    ))
      tile(
        title: label('Progress and notes'),
        icon: Icons.edit_note_outlined,
        onTap: () => open(Routes.progressNotes),
      ),
    if (context.watch<MeContextCubit>().context?.isFeatureOn('mastery') ==
            true &&
        allowsSchoolModule(context.watch<MeContextCubit>().context, 'mastery'))
      tile(
        title: label('Mastery map'),
        icon: Icons.account_tree_outlined,
        onTap: () => open(Routes.mastery),
      ),
    if (context.watch<MeContextCubit>().context?.roles.any(
              (role) => ['Counsellor', 'Safeguarding'].contains(role),
            ) ==
            true &&
        allowsSchoolModule(
          context.watch<MeContextCubit>().context,
          'wellbeing',
        ))
      tile(
        title: label('Incidents and counselling'),
        icon: Icons.support_agent,
        onTap: () => open(Routes.wellbeing),
      ),
    if (context.watch<MeContextCubit>().context?.isModuleOn('support_plans') ==
            true &&
        allowsSchoolModule(
          context.watch<MeContextCubit>().context,
          'support_plans',
        ))
      tile(
        title: label('Support plans'),
        icon: Icons.health_and_safety_outlined,
        onTap: () => open(Routes.supportPlans),
      ),
    if (context.watch<MeContextCubit>().context?.isFeatureOn('relief_cover') ==
            true &&
        allowsSchoolModule(
          context.watch<MeContextCubit>().context,
          'relief_cover',
        ))
      tile(
        title: label('Relief cover'),
        icon: Icons.event_available_outlined,
        onTap: () => open(Routes.reliefCover),
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
        title: label('academicRegisters'),
        icon: Icons.fact_check_outlined,
        onTap: () => open(Routes.academicRegisters),
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
    final school = me?.currentSchool;
    if (school == null || me?.isFeatureOn('academic_marks') != true) {
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
      schoolId: school.id,
      child: MarkGroupsScreen(
        repository: ApiMarksRepository(Api.client, schoolId: school.id),
        schoolName: school.name,
      ),
    );
  },
);

Route<dynamic> academicTimetableRoute() => AdaptivePageRoute(
  builder: (context) {
    final me = context.read<MeContextCubit>().context;
    final school = me?.currentSchool;
    if (school == null || me?.isFeatureOn('academic_timetable') != true) {
      return const ScreenScaffold(
        title: 'Academic timetable',
        body: Center(child: Text('Not available yet')),
      );
    }
    return AcademicContextBoundary(
      schoolId: school.id,
      child: TimetableScreen.withRepository(
        ApiTimetableRepository(Api.client, schoolId: school.id),
        title: school.name,
      ),
    );
  },
);

class AcademicNextPeriod extends StatelessWidget {
  const AcademicNextPeriod({super.key});
  @override
  Widget build(BuildContext context) {
    final me = context.watch<MeContextCubit>().context;
    final school = me?.currentSchool;
    if (school == null || me?.isFeatureOn('academic_timetable') != true)
      return const SizedBox.shrink();
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: NextPeriodCard(
        key: ValueKey('teacher:${school.id}'),
        scope: 'teacher:${school.id}',
        repository: ApiTimetableRepository(Api.client, schoolId: school.id),
        onOpen: () => Navigator.of(context).pushNamed(Routes.academicTimetable),
      ),
    );
  }
}

Route<dynamic> academicRegistersRoute() => AdaptivePageRoute(
  builder: (context) {
    final me = context.read<MeContextCubit>().context;
    final school = me?.currentSchool;
    final owner = context.read<AuthCubit>().getTeacherDetails().userId;
    if (school == null ||
        owner <= 0 ||
        me?.isFeatureOn('academic_registers') != true) {
      return const ScreenScaffold(
        title: 'Academic registers',
        body: Center(child: Text('Not available yet')),
      );
    }
    return AcademicContextBoundary(
      schoolId: school.id,
      child: RegisterHubScreen(
        repository: ApiRegisterRepository(Api.client, schoolId: school.id),
        store: () => SecureRegisterStore.open(owner),
      ),
    );
  },
);

Route<dynamic> academicReportsRoute() => AdaptivePageRoute(
  builder: (context) {
    final me = context.read<MeContextCubit>().context;
    final school = me?.currentSchool;
    if (school == null || me?.isFeatureOn('academic_reports') != true) {
      return const ScreenScaffold(
        title: 'Report cards',
        body: Center(child: Text('Not available yet')),
      );
    }
    return AcademicContextBoundary(
      schoolId: school.id,
      child: ReportHubScreen(
        repository: ApiReportRepository(Api.client, schoolId: school.id),
      ),
    );
  },
);

Route<dynamic> academicContentRoute(Object? arguments) => AdaptivePageRoute(
  builder: (context) {
    final session = context.read<MeContextCubit>();
    final schools = context.read<SchoolSelectionCubit>();
    final me = session.context;
    final school = me?.currentSchoolId, account = me?.user?.id;
    if (school == null ||
        account == null ||
        school <= 0 ||
        account <= 0 ||
        me?.isFeatureOn('academic_content') != true) {
      return const ScreenScaffold(
        title: 'Learning content',
        body: Center(child: Text('Not available yet')),
      );
    }
    final selectedSchool = schools.state.selectedId;
    return ContentContextBoundary(
      schoolId: selectedSchool ?? school,
      readerId: account,
      child: ContentHubScreen(
        repository: ApiAcademicContentRepository(
          Api.client,
          schoolId: school,
          readerId: account,
          contextActive: () =>
              session.context?.user?.id == account &&
              session.context?.currentSchoolId == school &&
              schools.state.selectedId == selectedSchool,
        ),
      ),
    );
  },
);

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

Route<dynamic> reliefCoverRoute() => AdaptivePageRoute(
  contentColumn: false,
  builder: (context) {
    final session = context.read<MeContextCubit>();
    final schools = context.read<SchoolSelectionCubit>();
    final me = session.context;
    final school = me?.currentSchoolId, account = me?.user?.id;
    if (school == null ||
        account == null ||
        me?.isFeatureOn('relief_cover') != true ||
        !allowsSchoolModule(me, 'relief_cover')) {
      return ScreenScaffold(
        title: SharedUiUtils.getTranslatedLabel(context, 'Relief cover'),
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
      child: CoverSessionBoundary(
        pinned: me!,
        child: CoverScreen(
          writable: () => !session.context!.isModuleReadOnly('relief_cover'),
          repository: ApiCoverRepository(
            Api.client,
            schoolId: school,
            accountId: account,
            contextActive: () =>
                coverAccessStillCurrent(me, session.context) &&
                (schools.state.selectedId == null ||
                    schools.state.selectedId == school),
          ),
        ),
      ),
    );
  },
);

Route<dynamic> activityGroupsRoute() => AdaptivePageRoute(
  contentColumn: false,
  builder: (context) {
    final session = context.read<MeContextCubit>();
    final schools = context.read<SchoolSelectionCubit>();
    final me = session.context;
    final school = me?.currentSchoolId, account = me?.user?.id;
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
        allowsSchoolModule(session.context, 'groups');
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
    final school = me?.currentSchoolId;
    final account = me?.user?.id;
    if (me == null ||
        school == null ||
        school <= 0 ||
        account == null ||
        account <= 0 ||
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
      child: MasterySessionBoundary(
        pinned: me,
        child: MasteryScreen(
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
                session.context?.isFeatureOn('mastery') == true &&
                allowsSchoolModule(session.context, 'mastery'),
          ),
        ),
      ),
    );
  },
);

Route<dynamic> supportPlansRoute() => AdaptivePageRoute(
  contentColumn: false,
  builder: (context) {
    final session = context.read<MeContextCubit>();
    final schools = context.read<SchoolSelectionCubit>();
    final me = session.context;
    final school = me?.currentSchoolId, account = me?.user?.id;
    if (school == null ||
        account == null ||
        me?.isModuleOn('support_plans') != true ||
        !allowsSchoolModule(me, 'support_plans')) {
      return ScreenScaffold(
        title: SharedUiUtils.getTranslatedLabel(context, 'Support plans'),
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
      child: SupportSessionBoundary(
        pinned: me!,
        builder: (active) => SupportScreen(
          repository: ApiSupportRepository(
            Api.client,
            schoolId: school,
            accountId: account,
            contextActive: () =>
                active() &&
                (schools.state.selectedId == null ||
                    schools.state.selectedId == school),
          ),
        ),
      ),
    );
  },
);

Route<dynamic> progressNotesRoute() => AdaptivePageRoute(
  contentColumn: false,
  builder: (context) {
    final session = context.read<MeContextCubit>();
    final schools = context.read<SchoolSelectionCubit>();
    final me = session.context;
    final school = me?.currentSchoolId;
    final account = me?.user?.id;
    if (me == null ||
        school == null ||
        school <= 0 ||
        account == null ||
        account <= 0 ||
        me.currentSchoolId != school ||
        (schools.state.selectedId != null &&
            schools.state.selectedId != school) ||
        !allowsSchoolModule(me, 'academics')) {
      return ScreenScaffold(
        title: SharedUiUtils.getTranslatedLabel(context, 'Progress and notes'),
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
      child: ProgressSessionBoundary(
        pinned: me,
        child: ProgressNotesScreen(
          canRead: () =>
              progressAccessStillCurrent(me, session.context) &&
              (schools.state.selectedId == null ||
                  schools.state.selectedId == school),
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
                allowsSchoolModule(session.context, 'academics'),
          ),
        ),
      ),
    );
  },
);

Route<dynamic> wellbeingRoute() => AdaptivePageRoute(
  contentColumn: false,
  builder: (context) {
    final session = context.read<MeContextCubit>();
    final schools = context.read<SchoolSelectionCubit>();
    final me = session.context;
    final school = me?.currentSchoolId, account = me?.user?.id;
    if (school == null ||
        account == null ||
        me?.isModuleOn('wellbeing') != true ||
        !allowsSchoolModule(me, 'wellbeing')) {
      return ScreenScaffold(
        title: SharedUiUtils.getTranslatedLabel(
          context,
          'Incidents and counselling',
        ),
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
      child: WellbeingSessionBoundary(
        pinned: me!,
        builder: (_, active) => WellbeingScreen(
          repository: ApiWellbeingRepository(
            Api.client,
            schoolId: school,
            accountId: account,
            contextActive: () =>
                active() &&
                (schools.state.selectedId == null ||
                    schools.state.selectedId == school),
          ),
        ),
      ),
    );
  },
);

Route<dynamic> riskInterventionsRoute() => AdaptivePageRoute(
  contentColumn: false,
  builder: (context) {
    final session = context.read<MeContextCubit>();
    final schools = context.read<SchoolSelectionCubit>();
    final me = session.context;
    final school = me?.currentSchoolId;
    final account = me?.user?.id;
    if (me == null ||
        school == null ||
        school <= 0 ||
        account == null ||
        account <= 0 ||
        me.currentSchoolId != school ||
        (schools.state.selectedId != null &&
            schools.state.selectedId != school) ||
        !allowsSchoolModule(me, 'early_warning')) {
      return ScreenScaffold(
        title: SharedUiUtils.getTranslatedLabel(context, 'Staff follow-up'),
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
      child: RiskSessionBoundary(
        pinned: me,
        child: RiskInterventionsScreen(
          canRead: () =>
              riskAccessStillCurrent(me, session.context) &&
              (schools.state.selectedId == null ||
                  schools.state.selectedId == school),
          repository: ApiRiskRepository(
            Api.client,
            schoolId: school,
            readerId: account,
            contextActive: () =>
                riskAccessStillCurrent(me, session.context) &&
                session.context?.user?.id == account &&
                session.context?.currentSchoolId == school &&
                (schools.state.selectedId == null ||
                    schools.state.selectedId == school) &&
                allowsSchoolModule(session.context, 'early_warning'),
          ),
        ),
      ),
    );
  },
);

Route<dynamic> schoolDisciplineRoute() => AdaptivePageRoute(
  contentColumn: false,
  builder: (context) {
    final session = context.read<MeContextCubit>();
    final schools = context.read<SchoolSelectionCubit>();
    final me = session.context;
    final school = me?.currentSchoolId;
    final account = me?.user?.id;
    if (me == null ||
        school == null ||
        school <= 0 ||
        account == null ||
        account <= 0 ||
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
      child: DisciplineSessionBoundary(
        pinned: me,
        child: SchoolDisciplineScreen(
          canRead: () =>
              disciplineAccessStillCurrent(me, session.context) &&
              (schools.state.selectedId == null ||
                  schools.state.selectedId == school),
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
                allowsSchoolModule(session.context, 'discipline'),
          ),
        ),
      ),
    );
  },
);

Route<dynamic> staffRecognitionRoute() => AdaptivePageRoute(
  contentColumn: false,
  builder: (context) {
    final session = context.read<MeContextCubit>(),
        schools = context.read<SchoolSelectionCubit>();
    final me = session.context,
        school = me?.currentSchoolId,
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
        modules.isEmpty ||
        !me.roles.any((role) => !['Parent', 'Student'].contains(role)) ||
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
      child: RecognitionSessionBoundary(
        pinned: me,
        modules: modules,
        builder: (active) => RecognitionScreen(
          repository: ApiRecognitionRepository(
            Api.client,
            schoolId: school,
            accountId: account,
            changes: recognitionLiveChanges(
              LiveUpdatesScope.eventsOf(context),
              refreshes: LiveUpdatesScope.refreshesOf(context),
              schoolId: school,
            ),
            contextActive: () =>
                active() &&
                (schools.state.selectedId == null ||
                    schools.state.selectedId == school),
          ),
          modules: modules,
        ),
      ),
    );
  },
);

class StaffRecognitionEntry extends StatelessWidget {
  const StaffRecognitionEntry({super.key});
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

Route<dynamic> dailyPracticeRoute() => AdaptivePageRoute(
  contentColumn: false,
  builder: (context) {
    final session = context.read<MeContextCubit>(),
        schools = context.read<SchoolSelectionCubit>();
    final me = session.context,
        school = me?.currentSchoolId,
        account = me?.user?.id;
    if (me == null ||
        school == null ||
        school <= 0 ||
        account == null ||
        account <= 0 ||
        !me.roles.contains('Teacher') ||
        !me.isModuleOn('daily_practice') ||
        !allowsSchoolModule(me, 'daily_practice')) {
      return ScreenScaffold(
        title: SharedUiUtils.getTranslatedLabel(
          context,
          'Practice question bank',
        ),
        body: const Center(child: Text('Not available for this account')),
      );
    }
    return ContentContextBoundary(
      schoolId: school,
      readerId: account,
      child: PracticeSessionBoundary(
        pinned: me,
        builder: (context, active) {
          bool read() =>
              active() &&
              (schools.state.selectedId == null ||
                  schools.state.selectedId == school);
          return PracticeReaderHost(
            create: () => PracticeBankScreen(
              repository: ApiPracticeRepository(
                Api.client,
                schoolId: school,
                readerId: account,
                contextActive: read,
              ),
              canRead: read,
              canWrite: () =>
                  read() &&
                  allowsSchoolModule(
                    session.context,
                    'daily_practice',
                    write: true,
                  ),
            ),
          );
        },
      ),
    );
  },
);

Route<dynamic> consultationsRoute(Object? argument) => AdaptivePageRoute(
  contentColumn: false,
  builder: (context) {
    final session = context.read<MeContextCubit>();
    final schools = context.read<SchoolSelectionCubit>();
    final me = session.context;
    final school = me?.currentSchoolId, account = me?.user?.id;
    final validArgument = argument == null || (argument is int && argument > 0);
    if (!validArgument ||
        session.state is! MeContextLoaded ||
        school == null ||
        account == null ||
        me?.isModuleOn('consultations') != true ||
        !allowsSchoolModule(me, 'consultations')) {
      return ScreenScaffold(
        title: SharedUiUtils.getTranslatedLabel(
          context,
          'Polls, surveys and elections',
        ),
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
        consultationAccessStillCurrent(pinned, session.context) &&
        (schools.state.selectedId == null ||
            schools.state.selectedId == school);
    return BlocListener<MeContextCubit, MeContextState>(
      listener: (context, state) {
        if (!active()) Navigator.of(context).popUntil((route) => route.isFirst);
      },
      child: ContentContextBoundary(
        schoolId: school,
        readerId: account,
        child: ConsultationSessionBoundary(
          pinned: pinned,
          builder: (_, generationActive) => ConsultationsScreen(
            repository: ApiConsultationRepository(
              Api.client,
              schoolId: school,
              accountId: account,
              contextActive: () => generationActive() && active(),
            ),
            initialId: argument as int?,
          ),
        ),
      ),
    );
  },
);

class ConsultationsEntry extends StatelessWidget {
  const ConsultationsEntry({super.key});
  @override
  Widget build(BuildContext context) =>
      BlocBuilder<MeContextCubit, MeContextState>(
        builder: (context, state) {
          if (state is! MeContextLoaded ||
              state.context.isModuleOn('consultations') != true ||
              !allowsSchoolModule(state.context, 'consultations')) {
            return const SizedBox.shrink();
          }
          return Card(
            child: ListTile(
              leading: const Icon(Icons.how_to_vote_outlined),
              title: Text(
                SharedUiUtils.getTranslatedLabel(
                  context,
                  'Polls, surveys and elections',
                ),
              ),
              trailing: const Icon(Icons.chevron_right),
              onTap: () =>
                  Navigator.of(context).pushNamed(Routes.consultations),
            ),
          );
        },
      );
}

Route<dynamic> staffPrivateGalleryRoute() => AdaptivePageRoute(
  contentColumn: false,
  builder: (context) {
    final session = context.read<MeContextCubit>(),
        schools = context.read<SchoolSelectionCubit>();
    final me = session.context,
        school = me?.currentSchoolId,
        account = me?.user?.id;

    if (me == null ||
        school == null ||
        school <= 0 ||
        account == null ||
        account <= 0 ||
        !(me.isModuleOn('gallery') && allowsSchoolModule(me, 'gallery')) ||
        !me.roles.any((role) => !['Parent', 'Student'].contains(role)) ||
        (schools.state.selectedId != null &&
            schools.state.selectedId != school)) {
      return ScreenScaffold(
        title: SharedUiUtils.getTranslatedLabel(context, 'Private photos'),
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
      child: GallerySessionBoundary(
        pinned: me,
        builder: (active) => PrivateGalleryScreen(
          repository: ApiPrivateGalleryRepository(
            Api.client,
            schoolId: school,
            accountId: account,
            contextActive: () =>
                active() &&
                (schools.state.selectedId == null ||
                    schools.state.selectedId == school),
          ),
        ),
      ),
    );
  },
);

class StaffPrivateGalleryEntry extends StatelessWidget {
  const StaffPrivateGalleryEntry({super.key});
  @override
  Widget build(BuildContext context) =>
      BlocBuilder<MeContextCubit, MeContextState>(
        builder: (context, state) {
          if (state.context?.isModuleOn('gallery') != true ||
              !allowsSchoolModule(state.context, 'gallery'))
            return const SizedBox.shrink();
          return Card(
            child: ListTile(
              leading: const Icon(Icons.photo_library_outlined),
              title: Text(
                SharedUiUtils.getTranslatedLabel(context, 'Private photos'),
              ),
              trailing: const Icon(Icons.chevron_right),
              onTap: () =>
                  Navigator.of(context).pushNamed(Routes.privateGallery),
            ),
          );
        },
      );
}

Route<dynamic> familyFormsRoute() => AdaptivePageRoute(
  contentColumn: false,
  builder: (context) {
    final session = context.read<MeContextCubit>(),
        schools = context.read<SchoolSelectionCubit>();
    final me = session.context,
        school = me?.currentSchoolId,
        account = me?.user?.id;
    if (session.state is! MeContextLoaded ||
        school == null ||
        account == null ||
        me?.isModuleOn('family_forms') != true ||
        !allowsSchoolModule(me, 'family_forms') ||
        !(me!.roles.any((role) => role != 'Parent' && role != 'Student'))) {
      return ScreenScaffold(
        title: SharedUiUtils.getTranslatedLabel(context, 'Activity consent'),
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
    final pinned = me;
    bool active() =>
        session.state is MeContextLoaded &&
        familyFormAccessStillCurrent(pinned, session.context) &&
        (schools.state.selectedId == null ||
            schools.state.selectedId == school);
    return ContentContextBoundary(
      schoolId: school,
      readerId: account,
      child: FamilyFormSessionBoundary(
        pinned: pinned,
        builder: (_, generationActive) => FamilyFormsScreen(
          staffOnly: true,
          repository: ApiFamilyFormRepository(
            Api.client,
            schoolId: school,
            accountId: account,
            contextActive: () => generationActive() && active(),
          ),
        ),
      ),
    );
  },
);

class FamilyFormsEntry extends StatelessWidget {
  const FamilyFormsEntry({super.key});
  @override
  Widget build(
    BuildContext context,
  ) => BlocBuilder<MeContextCubit, MeContextState>(
    builder: (context, state) {
      final me = state.context;
      if (state is! MeContextLoaded ||
          me?.currentSchoolId == null ||
          me?.user?.id == null ||
          me?.isModuleOn('family_forms') != true ||
          !allowsSchoolModule(me, 'family_forms') ||
          !(me!.roles.any((role) => role != 'Parent' && role != 'Student'))) {
        return const SizedBox.shrink();
      }
      return AvailableOnly(
        featureKey:
            'family-forms:${me.currentSchoolId}:${me.user!.id}:${me.roles.join(',')}:${me.children.hashCode}',
        probe: () => familyFormsDeployed(
          ApiFamilyFormRepository(
            Api.client,
            schoolId: me.currentSchoolId!,
            accountId: me.user!.id,
            contextActive: () =>
                context.read<MeContextCubit>().state is MeContextLoaded &&
                familyFormAccessStillCurrent(
                  me,
                  context.read<MeContextCubit>().context,
                ),
          ),
          staffOnly: true,
        ),
        child: Card(
          child: ListTile(
            leading: const Icon(Icons.family_restroom_outlined),
            title: Text(
              SharedUiUtils.getTranslatedLabel(context, 'Activity consent'),
            ),
            trailing: const Icon(Icons.chevron_right),
            onTap: () => Navigator.of(context).pushNamed(Routes.familyForms),
          ),
        ),
      );
    },
  );
}

Route<dynamic> familyVisitsRoute() => AdaptivePageRoute(
  contentColumn: false,
  builder: (context) {
    final session = context.read<MeContextCubit>(),
        schools = context.read<SchoolSelectionCubit>();
    final me = session.context,
        school = me?.currentSchoolId,
        account = me?.user?.id;
    if (session.state is! MeContextLoaded ||
        school == null ||
        account == null ||
        me?.isModuleOn('family_visits') != true ||
        !allowsSchoolModule(me, 'family_visits') ||
        !(me!.roles.any((role) => role != 'Parent' && role != 'Student'))) {
      return ScreenScaffold(
        title: SharedUiUtils.getTranslatedLabel(
          context,
          'Meetings and early collection',
        ),
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
    final pinned = me;
    bool active() =>
        session.state is MeContextLoaded &&
        familyVisitAccessStillCurrent(pinned, session.context) &&
        (schools.state.selectedId == null ||
            schools.state.selectedId == school);
    return ContentContextBoundary(
      schoolId: school,
      readerId: account,
      child: FamilyVisitSessionBoundary(
        pinned: pinned,
        builder: (_, generationActive) => FamilyVisitsScreen(
          staffOnly: true,
          repository: ApiFamilyVisitRepository(
            Api.client,
            schoolId: school,
            accountId: account,
            contextActive: () => generationActive() && active(),
          ),
        ),
      ),
    );
  },
);

class FamilyVisitsEntry extends StatelessWidget {
  const FamilyVisitsEntry({super.key});
  @override
  Widget build(
    BuildContext context,
  ) => BlocBuilder<MeContextCubit, MeContextState>(
    builder: (context, state) {
      final me = state.context;
      if (state is! MeContextLoaded ||
          me?.currentSchoolId == null ||
          me?.user?.id == null ||
          me?.isModuleOn('family_visits') != true ||
          !allowsSchoolModule(me, 'family_visits') ||
          !(me!.roles.any((role) => role != 'Parent' && role != 'Student'))) {
        return const SizedBox.shrink();
      }
      return AvailableOnly(
        featureKey:
            'family-visits:${me.currentSchoolId}:${me.user!.id}:${me.roles.join(',')}:${me.children.hashCode}',
        probe: () => familyVisitsDeployed(
          ApiFamilyVisitRepository(
            Api.client,
            schoolId: me.currentSchoolId!,
            accountId: me.user!.id,
            contextActive: () =>
                context.read<MeContextCubit>().state is MeContextLoaded &&
                familyVisitAccessStillCurrent(
                  me,
                  context.read<MeContextCubit>().context,
                ),
          ),
          staffOnly: true,
        ),
        child: Card(
          child: ListTile(
            leading: const Icon(Icons.event_available_outlined),
            title: Text(
              SharedUiUtils.getTranslatedLabel(
                context,
                'Meetings and early collection',
              ),
            ),
            trailing: const Icon(Icons.chevron_right),
            onTap: () => Navigator.of(context).pushNamed(Routes.familyVisits),
          ),
        ),
      );
    },
  );
}

Route<dynamic> schoolCardsRoute() => AdaptivePageRoute(
  contentColumn: false,
  builder: (context) {
    final session = context.read<MeContextCubit>();
    final schools = context.read<SchoolSelectionCubit>();
    final me = session.context;
    final school = me?.currentSchoolId, account = me?.user?.id;
    if (session.state is! MeContextLoaded ||
        school == null ||
        account == null ||
        me?.isModuleOn('cards') != true ||
        !allowsSchoolModule(me, 'cards')) {
      return ScreenScaffold(
        title: SharedUiUtils.getTranslatedLabel(
          context,
          'School identity cards',
        ),
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
        cardAccessStillCurrent(pinned, session.context) &&
        (schools.state.selectedId == null ||
            schools.state.selectedId == school);
    final people = <CardPersonChoice>[
      CardPersonChoice(
        account,
        pinned.user!.name,
        pinned.roles.any((r) => r != 'Parent' && r != 'Student')
            ? 'staff'
            : pinned.roles.contains('Student')
            ? 'learner'
            : 'guardian',
      ),
      if (pinned.roles.contains('Parent'))
        for (final child in pinned.children ?? <MeChild>[])
          if (child.child.userId > 0 &&
              child.child.school.id == school &&
              child.approved == true)
            CardPersonChoice(child.child.userId, child.child.name, 'learner'),
    ];
    return BlocListener<MeContextCubit, MeContextState>(
      listener: (context, state) {
        if (!active()) Navigator.of(context).popUntil((route) => route.isFirst);
      },
      child: ContentContextBoundary(
        schoolId: school,
        readerId: account,
        child: CardSessionBoundary(
          pinned: pinned,
          builder: (_, generationActive) => SchoolCardsScreen(
            repository: ApiCardRepository(
              Api.client,
              schoolId: school,
              accountId: account,
              contextActive: () => generationActive() && active(),
            ),
            people: people,
            schoolName: pinned.currentSchool!.name,
          ),
        ),
      ),
    );
  },
);

class SchoolCardsEntry extends StatelessWidget {
  const SchoolCardsEntry({super.key});
  @override
  Widget build(
    BuildContext context,
  ) => BlocBuilder<MeContextCubit, MeContextState>(
    builder: (context, state) {
      final me = state.context;
      if (state is! MeContextLoaded ||
          me?.currentSchoolId == null ||
          me?.user?.id == null ||
          me?.isModuleOn('cards') != true ||
          !allowsSchoolModule(me, 'cards'))
        return const SizedBox.shrink();
      return AvailableOnly(
        featureKey:
            'secure-cards:${me!.currentSchoolId}:${me.user!.id}:${me.roles.join(',')}',
        probe: () => cardsDeployed(
          ApiCardRepository(
            Api.client,
            schoolId: me.currentSchoolId!,
            accountId: me.user!.id,
            contextActive: () =>
                context.read<MeContextCubit>().state is MeContextLoaded &&
                cardAccessStillCurrent(
                  me,
                  context.read<MeContextCubit>().context,
                ),
          ),
        ),
        child: Card(
          child: ListTile(
            leading: const Icon(Icons.badge_outlined),
            title: Text(
              SharedUiUtils.getTranslatedLabel(
                context,
                'School identity cards',
              ),
            ),
            trailing: const Icon(Icons.chevron_right),
            onTap: () => Navigator.of(context).pushNamed(Routes.schoolCards),
          ),
        ),
      );
    },
  );
}

Route<dynamic> myGradeRoute() => AdaptivePageRoute(
  contentColumn: false,
  builder: (context) {
    final session = context.read<MeContextCubit>();
    final schools = context.read<SchoolSelectionCubit>();
    final me = session.context;
    final school = me?.currentSchoolId, account = me?.user?.id;
    if (me == null ||
        school == null ||
        school < 1 ||
        account == null ||
        account < 1 ||
        me.featureFlags['my_grade'] != true ||
        !me.isModuleOn('my_grade') ||
        (schools.state.selectedId != null &&
            schools.state.selectedId != school)) {
      return ScreenScaffold(
        title: SharedUiUtils.getTranslatedLabel(context, 'My grade'),
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
      child: MyGradeSessionBoundary(
        pinned: me,
        builder: (_, active) => MyGradeClientScreen(
          client: Api.client,
          schoolId: school,
          readerId: account,
          contextChanges: Stream<void>.multi((changes) {
            final profile = session.stream.listen((_) => changes.add(null));
            final selection = schools.stream.listen((_) => changes.add(null));
            changes.onCancel = () {
              profile.cancel();
              selection.cancel();
            };
          }),
          contextActive: () =>
              active() &&
              (schools.state.selectedId == null ||
                  schools.state.selectedId == school),
        ),
      ),
    );
  },
);

Route<dynamic> teachingWorkspaceRoute({
  bool insights = false,
}) => AdaptivePageRoute(
  contentColumn: false,
  builder: (context) {
    final session = context.read<MeContextCubit>();
    final schools = context.read<SchoolSelectionCubit>();
    final me = session.context;
    final school = me?.currentSchoolId, account = me?.user?.id;
    final module = insights ? 'test_insights' : 'teaching_assistant';
    if (me == null ||
        school == null ||
        school < 1 ||
        account == null ||
        account < 1 ||
        me.featureFlags[module] != true ||
        !me.isModuleOn(module) ||
        (schools.state.selectedId != null &&
            schools.state.selectedId != school)) {
      return ScreenScaffold(
        title: SharedUiUtils.getTranslatedLabel(context, 'Teaching workspace'),
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
      child: TeachingSessionBoundary(
        pinned: me,
        module: module,
        builder: (_, active) {
          bool current() =>
              active() &&
              (schools.state.selectedId == null ||
                  schools.state.selectedId == school);
          return TeachingScreen(
            repository: ApiTeachingRepository(
              Api.client,
              schoolId: school,
              readerId: account,
              contextActive: current,
            ),
            insightsOnly: insights,
            canRead: current,
            canWrite: () =>
                current() && session.context?.isModuleReadOnly(module) == false,
            selectCameraPhoto: selectTeachingCameraPhoto,
            exportPdf: (bytes, name) async {
              if (!current()) return;
              await DeviceFiles.saveDocument(
                bytes,
                'private-exports/$account/$school/$name',
              );
            },
          );
        },
      ),
    );
  },
);

Route<dynamic> examCoachRoute() => AdaptivePageRoute(
  contentColumn: false,
  builder: (context) {
    final session = context.read<MeContextCubit>();
    final schools = context.read<SchoolSelectionCubit>();
    final me = session.context;
    final school = me?.currentSchoolId, account = me?.user?.id;
    if (me == null ||
        school == null ||
        account == null ||
        !me.roles.contains('Teacher') ||
        !me.isModuleOn('exam_coach') ||
        !allowsSchoolModule(me, 'exam_coach') ||
        (schools.state.selectedId != null &&
            schools.state.selectedId != school)) {
      return ScreenScaffold(
        title: SharedUiUtils.getTranslatedLabel(context, 'Exam Coach'),
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
      child: ExamSessionBoundary(
        pinned: me,
        staff: true,
        builder: (_, active) {
          bool current() =>
              active() &&
              (schools.state.selectedId == null ||
                  schools.state.selectedId == school);
          return ExamScreen(
            repository: ApiExamRepository(
              Api.client,
              schoolId: school,
              readerId: account,
              contextActive: current,
            ),
            staff: true,
            canRead: current,
            canWrite: () =>
                current() &&
                allowsSchoolModule(session.context, 'exam_coach', write: true),
            selectCameraPhoto: selectTeachingCameraPhoto,
            exportPdf: (bytes, name) async {
              if (!current()) return;
              await DeviceFiles.saveDocument(
                bytes,
                'private-exports/$account/$school/$name',
              );
            },
          );
        },
      ),
    );
  },
);

class ExamCoachEntry extends StatelessWidget {
  const ExamCoachEntry({super.key});
  @override
  Widget build(BuildContext context) =>
      BlocBuilder<MeContextCubit, MeContextState>(
        builder: (context, state) {
          final me = state.context;
          if (state is! MeContextLoaded ||
              me?.roles.contains('Teacher') != true ||
              me?.isModuleOn('exam_coach') != true ||
              !allowsSchoolModule(me, 'exam_coach'))
            return const SizedBox.shrink();
          return Card(
            child: ListTile(
              leading: const Icon(Icons.menu_book_outlined),
              title: Text(
                SharedUiUtils.getTranslatedLabel(context, 'Exam Coach'),
              ),
              trailing: const Icon(Icons.chevron_right),
              onTap: () => Navigator.of(context).pushNamed(Routes.examCoach),
            ),
          );
        },
      );
}
