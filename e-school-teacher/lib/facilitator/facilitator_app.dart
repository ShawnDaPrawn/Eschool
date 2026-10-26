import 'package:eschool_teacher/utils/teachingCamera.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_session/eschool_session.dart' show AvailableOnly;
import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';

import 'facilitator_repository.dart';

class FacilitatorApp extends StatefulWidget {
  const FacilitatorApp({required this.repository, super.key});
  final FacilitatorRepository repository;
  @override
  State<FacilitatorApp> createState() => _FacilitatorAppState();
}

class _FacilitatorAppState extends State<FacilitatorApp>
    with WidgetsBindingObserver {
  bool _hidden = false;
  Locale _locale = const Locale('en');
  static const _languages = [
    AppLanguage(languageCode: 'en', languageName: 'English'),
    AppLanguage(languageCode: 'af', languageName: 'Afrikaans'),
    AppLanguage(languageCode: 'zu', languageName: 'isiZulu'),
    AppLanguage(languageCode: 'ar', languageName: 'العربية'),
  ];
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      setState(() => _hidden = false);
      widget.repository.refresh();
    } else if ([
      AppLifecycleState.inactive,
      AppLifecycleState.paused,
      AppLifecycleState.hidden,
    ].contains(state)) {
      setState(() => _hidden = true);
    }
  }

  Widget _language(BuildContext context) => DropdownButtonHideUnderline(
    child: DropdownButton<String>(
      value: _locale.languageCode,
      alignment: Alignment.center,
      items: [
        for (final lang in _languages)
          DropdownMenuItem(
            value: lang.languageCode,
            child: Text(lang.languageName),
          ),
      ],
      onChanged: (code) {
        if (code != null) setState(() => _locale = Locale(code));
      },
    ),
  );
  @override
  Widget build(BuildContext context) => AnimatedBuilder(
    animation: widget.repository,
    builder: (_, __) {
      final repo = widget.repository;
      return MaterialApp(
        key: ValueKey(repo.epoch),
        title: 'NavigateEd Facilitator',
        theme: AppTheme.light(),
        locale: _locale,
        supportedLocales: [
          for (final lang in _languages) Locale(lang.languageCode),
        ],
        localizationsDelegates: const [
          AppLocalizationDelegate(_languages),
          GlobalMaterialLocalizations.delegate,
          GlobalWidgetsLocalizations.delegate,
          GlobalCupertinoLocalizations.delegate,
        ],
        debugShowCheckedModeBanner: false,
        home: Builder(
          builder: (context) => repo.profile == null
              ? _StaffSignIn(repository: repo, language: _language(context))
              : _StaffHome(repository: repo, language: _language(context)),
        ),
        builder: (context, child) {
          final waiting = repo.signedIn && !repo.ready;
          final profile = repo.profile;
          final generation = repo.epoch;
          final school = profile?.schoolId ?? 0, account = profile?.userId ?? 0;
          return LiveUpdatesHost(
            client: repo.client,
            schoolId: school,
            accountId: account,
            enabled: repo.ready && profile?.features['live_updates'] == true,
            authorizationKey: generation,
            contextActive: () =>
                repo.matches(generation, school, account) &&
                repo.profile?.features['live_updates'] == true,
            origin: 'https://teach.navigateed.co.za',
            child: Stack(
              children: [
                Offstage(offstage: _hidden || waiting, child: child!),
                if (waiting && !_hidden)
                  Scaffold(
                    body: Center(
                      child: Padding(
                        padding: const EdgeInsets.all(24),
                        child: Column(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            if (repo.busy) const CircularProgressIndicator(),
                            if (repo.error != null) ...[
                              Text(
                                SharedUiUtils.getTranslatedLabel(
                                  context,
                                  repo.error!,
                                ),
                              ),
                              const SizedBox(height: 12),
                              FilledButton(
                                onPressed: repo.refresh,
                                child: Text(
                                  SharedUiUtils.getTranslatedLabel(
                                    context,
                                    'Retry',
                                  ),
                                ),
                              ),
                              TextButton(
                                onPressed: repo.signOut,
                                child: Text(
                                  SharedUiUtils.getTranslatedLabel(
                                    context,
                                    'Sign out',
                                  ),
                                ),
                              ),
                            ],
                          ],
                        ),
                      ),
                    ),
                  ),
              ],
            ),
          );
        },
      );
    },
  );
}

class _StaffSignIn extends StatefulWidget {
  const _StaffSignIn({required this.repository, required this.language});
  final FacilitatorRepository repository;
  final Widget language;
  @override
  State<_StaffSignIn> createState() => _StaffSignInState();
}

class _StaffSignInState extends State<_StaffSignIn> {
  final _form = GlobalKey<FormState>();
  late final TextEditingController _school;
  final _email = TextEditingController(),
      _password = TextEditingController(),
      _new = TextEditingController(),
      _confirm = TextEditingController();
  StaffSignInReply? _setup;
  final _accepted = <String>{};
  String? _error;
  String l(String s) => SharedUiUtils.getTranslatedLabel(context, s);
  @override
  void initState() {
    super.initState();
    _school = TextEditingController(text: widget.repository.preferredSchool);
  }

  @override
  void dispose() {
    for (final c in [_school, _email, _password, _new, _confirm]) {
      c.dispose();
    }
    super.dispose();
  }

  void _credentialsChanged() {
    if (_setup != null) {
      setState(() {
        _setup = null;
        _accepted.clear();
        _new.clear();
        _confirm.clear();
      });
    }
  }

  Future<void> _signIn() async {
    if (widget.repository.busy || !_form.currentState!.validate()) return;
    if (_setup != null &&
        _setup!.documents.any((d) => !_accepted.contains(d.document))) {
      setState(() => _error = 'Read and accept each required document.');
      return;
    }
    setState(() => _error = null);
    final input = <String, dynamic>{
      'school': _school.text.trim().toLowerCase(),
      'email': _email.text.trim(),
      'password': _password.text,
      if (_setup?.passwordRequired ?? false) 'new_password': _new.text,
      if (_setup?.passwordRequired ?? false)
        'new_password_confirmation': _confirm.text,
      if (_setup != null)
        'acceptances': {
          for (final d in _setup!.documents)
            if (_accepted.contains(d.document)) d.document: d.version,
        },
    };
    try {
      final result = await widget.repository.signIn(input);
      if (!mounted) return;
      if (!result.authenticated) {
        setState(() {
          _setup = result;
          _accepted.clear();
          _error = 'Complete the required sign-in setup.';
        });
      }
    } catch (e) {
      if (mounted) {
        setState(
          () => _error = e is ApiException && e.isApiMessage
              ? e.errorMessage
              : 'Could not sign in. Check your details and try again.',
        );
      }
    }
  }

  Widget _field(
    TextEditingController controller,
    String label, {
    bool secret = false,
    bool credential = false,
    bool confirm = false,
  }) => TextFormField(
    controller: controller,
    decoration: InputDecoration(labelText: l(label)),
    obscureText: secret,
    enableSuggestions: !secret,
    autocorrect: !secret,
    maxLength: secret ? 128 : 191,
    enabled: !widget.repository.busy,
    onChanged: credential ? (_) => _credentialsChanged() : null,
    validator: (value) {
      if ((value ?? '').trim().isEmpty) return l('Required');
      if (confirm && value != _new.text) return l('Passwords do not match.');
      return null;
    },
  );
  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(
      title: const Text('NavigateEd Facilitator'),
      actions: [
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 12),
          child: widget.language,
        ),
      ],
    ),
    body: SafeArea(
      child: Center(
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 640),
          child: Form(
            key: _form,
            child: ListView(
              padding: const EdgeInsets.all(24),
              children: [
                Text(
                  l('Facilitator sign-in'),
                  style: Theme.of(context).textTheme.headlineSmall,
                ),
                Text(
                  l('Use the school code and account your school assigned.'),
                ),
                _field(_school, 'School code', credential: true),
                _field(_email, 'Email or handle', credential: true),
                _field(_password, 'Password', secret: true, credential: true),
                if (_setup?.passwordRequired ?? false) ...[
                  _field(_new, 'New password', secret: true),
                  _field(
                    _confirm,
                    'Confirm new password',
                    secret: true,
                    confirm: true,
                  ),
                ],
                if (_setup != null)
                  for (final document in _setup!.documents)
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              document.title,
                              style: Theme.of(context).textTheme.titleLarge,
                            ),
                            SelectableText(document.text),
                            CheckboxListTile(
                              contentPadding: EdgeInsets.zero,
                              title: Text(
                                l('I have read and accept this document.'),
                              ),
                              value: _accepted.contains(document.document),
                              onChanged: widget.repository.busy
                                  ? null
                                  : (value) => setState(() {
                                      if (value == true) {
                                        _accepted.add(document.document);
                                      } else {
                                        _accepted.remove(document.document);
                                      }
                                    }),
                            ),
                          ],
                        ),
                      ),
                    ),
                if (_error != null)
                  Padding(
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    child: Semantics(liveRegion: true, child: Text(l(_error!))),
                  ),
                if (widget.repository.busy) const LinearProgressIndicator(),
                FilledButton(
                  onPressed: widget.repository.busy ? null : _signIn,
                  child: Text(
                    l(_setup == null ? 'Sign in' : 'Complete sign-in'),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    ),
  );
}

class _StaffHome extends StatelessWidget {
  const _StaffHome({required this.repository, required this.language});
  final FacilitatorRepository repository;
  final Widget language;
  @override
  Widget build(BuildContext context) {
    final profile = repository.profile!;
    String l(String s) => SharedUiUtils.getTranslatedLabel(context, s);
    final generation = repository.epoch,
        school = profile.schoolId,
        user = profile.userId;
    Future<void> open(String module, Widget child) async {
      await Navigator.of(context).push(
        MaterialPageRoute<void>(
          builder: (_) => AnimatedBuilder(
            animation: repository,
            builder: (_, __) =>
                repository.matches(generation, school, user) &&
                    repository.profile?.features[module] == true
                ? child
                : const SizedBox.shrink(),
          ),
        ),
      );
    }

    return Scaffold(
      appBar: AppBar(
        title: Text(l('Your school activities')),
        actions: [
          language,
          IconButton(
            tooltip: l('Refresh staff access'),
            onPressed: repository.busy ? null : repository.refresh,
            icon: const Icon(Icons.refresh),
          ),
          IconButton(
            tooltip: l('Sign out'),
            onPressed: repository.signOut,
            icon: const Icon(Icons.logout),
          ),
        ],
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Text(profile.name, style: Theme.of(context).textTheme.headlineSmall),
          DropdownButtonFormField<int>(
            initialValue: profile.schoolId,
            isExpanded: true,
            decoration: InputDecoration(labelText: l('School')),
            items: [
              for (final s in profile.schools)
                DropdownMenuItem(value: s.id, child: Text(s.name)),
            ],
            onChanged: (id) {
              if (id != null) repository.switchSchool(id);
            },
          ),
          const SizedBox(height: 16),
          if ([
            'house_points',
            'awards',
          ].any((key) => profile.features[key] == true))
            Card(
              child: ListTile(
                leading: const Icon(Icons.emoji_events_outlined),
                title: Text(l('Houses and awards')),
                trailing: const Icon(Icons.chevron_right),
                onTap: () {
                  final modules = [
                    for (final key in ['house_points', 'awards'])
                      if (profile.features[key] == true) key,
                  ];
                  open(
                    modules.first,
                    RecognitionScreen(
                      repository: ApiRecognitionRepository(
                        repository.client,
                        schoolId: school,
                        accountId: user,
                        changes: recognitionLiveChanges(
                          LiveUpdatesScope.eventsOf(context),
                          refreshes: LiveUpdatesScope.refreshesOf(context),
                          schoolId: school,
                        ),
                        contextActive: () =>
                            repository.matches(generation, school, user) &&
                            modules.every(
                              (key) =>
                                  repository.profile?.features[key] == true,
                            ),
                      ),
                      modules: modules,
                    ),
                  );
                },
              ),
            ),
          if (profile.features['family_forms'] == true)
            Card(
              child: ListTile(
                leading: const Icon(Icons.family_restroom_outlined),
                title: Text(l('Activity consent')),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => open(
                  'family_forms',
                  FamilyFormsScreen(
                    staffOnly: true,
                    repository: ApiFamilyFormRepository(
                      repository.client,
                      schoolId: school,
                      accountId: user,
                      contextActive: () =>
                          repository.matches(generation, school, user) &&
                          repository.profile?.features['family_forms'] == true,
                    ),
                  ),
                ),
              ),
            ),
          AvailableOnly(
            featureKey: 'staff-family-visits:$generation:$school:$user',
            probe: () => familyVisitsDeployed(
              ApiFamilyVisitRepository(
                repository.client,
                schoolId: school,
                accountId: user,
                contextActive: () =>
                    repository.matches(generation, school, user),
              ),
              staffOnly: true,
            ),
            child: Card(
              child: ListTile(
                leading: const Icon(Icons.event_available_outlined),
                title: Text(l('Meetings and early collection')),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => Navigator.of(context).push(
                  MaterialPageRoute<void>(
                    builder: (_) => AnimatedBuilder(
                      animation: repository,
                      builder: (_, __) {
                        if (!repository.matches(generation, school, user))
                          return ScreenScaffold(
                            title: l('Meetings and early collection'),
                            body: Center(
                              child: Text(
                                l(
                                  'Your meeting access changed. Reopen this screen.',
                                ),
                              ),
                            ),
                          );
                        return FamilyVisitsScreen(
                          staffOnly: true,
                          repository: ApiFamilyVisitRepository(
                            repository.client,
                            schoolId: school,
                            accountId: user,
                            contextActive: () =>
                                repository.matches(generation, school, user),
                          ),
                        );
                      },
                    ),
                  ),
                ),
              ),
            ),
          ),
          if (profile.features['cards'] == true)
            Card(
              child: ListTile(
                leading: const Icon(Icons.badge_outlined),
                title: Text(l('School identity cards')),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => open(
                  'cards',
                  SchoolCardsScreen(
                    selfOnly: true,
                    repository: ApiCardRepository(
                      repository.client,
                      schoolId: school,
                      accountId: user,
                      contextActive: () =>
                          repository.matches(generation, school, user) &&
                          repository.profile?.features['cards'] == true,
                    ),
                    people: [CardPersonChoice(user, profile.name, 'staff')],
                    schoolName: profile.schools
                        .firstWhere((s) => s.id == school)
                        .name,
                  ),
                ),
              ),
            ),
          if (profile.features['gallery'] == true)
            Card(
              child: ListTile(
                leading: const Icon(Icons.photo_library_outlined),
                title: Text(l('Private photos')),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => open(
                  'gallery',
                  PrivateGalleryScreen(
                    repository: ApiPrivateGalleryRepository(
                      repository.client,
                      schoolId: school,
                      accountId: user,
                      contextActive: () =>
                          repository.matches(generation, school, user) &&
                          repository.profile?.features['gallery'] == true,
                    ),
                  ),
                ),
              ),
            ),
          if (profile.features['consultations'] == true)
            Card(
              child: ListTile(
                leading: const Icon(Icons.how_to_vote_outlined),
                title: Text(l('Polls, surveys and elections')),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => open(
                  'consultations',
                  ConsultationsScreen(
                    repository: ApiConsultationRepository(
                      repository.client,
                      schoolId: school,
                      accountId: user,
                      contextActive: () =>
                          repository.matches(generation, school, user) &&
                          repository.profile?.features['consultations'] == true,
                    ),
                  ),
                ),
              ),
            ),
          if (profile.features['private_notices'] == true)
            Card(
              child: ListTile(
                leading: const Icon(Icons.notifications_none),
                title: Text(l('Private staff updates')),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => open(
                  'private_notices',
                  PrivateStaffInboxScreen(
                    repository: ApiPrivateStaffNoticeRepository(
                      repository.client,
                      schoolId: school,
                      accountId: user,
                      contextActive: () =>
                          repository.matches(generation, school, user) &&
                          repository.profile?.features['private_notices'] ==
                              true,
                    ),
                    canOpen: (feature) =>
                        repository.matches(generation, school, user) &&
                        ((feature == 'progress' &&
                                repository.profile?.roles.contains('Teacher') ==
                                    true &&
                                repository
                                        .profile
                                        ?.features['private_notices'] ==
                                    true) ||
                            (feature == 'discipline' &&
                                repository.profile?.features['discipline'] ==
                                    true) ||
                            (feature == 'wellbeing' &&
                                repository.profile?.features['counselling'] ==
                                    true) ||
                            (feature == 'consultations' &&
                                repository.profile?.features['consultations'] ==
                                    true)),
                    openFeature: (feature) async {
                      if (feature == 'progress' &&
                          repository.matches(generation, school, user) &&
                          repository.profile?.roles.contains('Teacher') ==
                              true &&
                          repository.profile?.features['private_notices'] ==
                              true) {
                        bool activeProgress() =>
                            repository.matches(generation, school, user) &&
                            repository.profile?.roles.contains('Teacher') ==
                                true &&
                            repository.profile?.features['private_notices'] ==
                                true;
                        await open(
                          'private_notices',
                          ProgressNotesScreen(
                            repository: ApiProgressRepository(
                              repository.client,
                              schoolId: school,
                              readerId: user,
                              contextActive: activeProgress,
                            ),
                            canRead: activeProgress,
                          ),
                        );
                        return;
                      }
                      if (feature == 'discipline' &&
                          repository.matches(generation, school, user) &&
                          repository.profile?.features['discipline'] == true) {
                        bool activeDiscipline() =>
                            repository.matches(generation, school, user) &&
                            repository.profile?.features['discipline'] == true;
                        await open(
                          'discipline',
                          SchoolDisciplineScreen(
                            repository: ApiDisciplineRepository(
                              repository.client,
                              schoolId: school,
                              readerId: user,
                              contextActive: activeDiscipline,
                            ),
                            canRead: activeDiscipline,
                          ),
                        );
                        return;
                      }
                      if (feature == 'consultations' &&
                          repository.matches(generation, school, user) &&
                          repository.profile?.features['consultations'] ==
                              true) {
                        await open(
                          'consultations',
                          ConsultationsScreen(
                            repository: ApiConsultationRepository(
                              repository.client,
                              schoolId: school,
                              accountId: user,
                              contextActive: () =>
                                  repository.matches(
                                    generation,
                                    school,
                                    user,
                                  ) &&
                                  repository
                                          .profile
                                          ?.features['consultations'] ==
                                      true,
                            ),
                          ),
                        );
                        return;
                      }
                      if (feature != 'wellbeing' ||
                          !repository.matches(generation, school, user) ||
                          repository.profile?.features['counselling'] != true) {
                        return;
                      }
                      await open(
                        'counselling',
                        WellbeingScreen(
                          repository: ApiWellbeingRepository(
                            repository.client,
                            schoolId: school,
                            accountId: user,
                            contextActive: () =>
                                repository.matches(generation, school, user) &&
                                repository.profile?.features['counselling'] ==
                                    true,
                          ),
                        ),
                      );
                    },
                  ),
                ),
              ),
            ),
          if (profile.features['daily_practice'] == true)
            Card(
              child: ListTile(
                leading: const Icon(Icons.psychology_alt_outlined),
                title: Text(l('Practice question bank')),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => open(
                  'daily_practice',
                  PracticeBankScreen(
                    repository: ApiPracticeRepository(
                      repository.client,
                      schoolId: school,
                      readerId: user,
                      contextActive: () =>
                          repository.matches(generation, school, user) &&
                          repository.profile?.features['daily_practice'] ==
                              true,
                    ),
                    canRead: () =>
                        repository.matches(generation, school, user) &&
                        repository.profile?.features['daily_practice'] == true,
                    canWrite: () =>
                        repository.matches(generation, school, user) &&
                        repository.profile?.features['daily_practice'] == true,
                  ),
                ),
              ),
            ),
          if (profile.features['exam_coach'] == true)
            Card(
              child: ListTile(
                leading: const Icon(Icons.menu_book_outlined),
                title: Text(l('Exam Coach')),
                trailing: const Icon(Icons.chevron_right),
                onTap: () {
                  bool current() =>
                      repository.matches(generation, school, user) &&
                      repository.profile?.features['exam_coach'] == true;
                  open(
                    'exam_coach',
                    ExamScreen(
                      repository: ApiExamRepository(
                        repository.client,
                        schoolId: school,
                        readerId: user,
                        contextActive: current,
                      ),
                      staff: true,
                      canRead: current,
                      canWrite: current,
                      selectCameraPhoto: selectTeachingCameraPhoto,
                      exportPdf: (bytes, name) async {
                        if (!current()) return;
                        await DeviceFiles.saveDocument(
                          bytes,
                          'private-exports/$user/$school/$name',
                        );
                      },
                    ),
                  );
                },
              ),
            ),
          for (final entry in <(String, String, bool)>[
            ('teaching_assistant', 'Teaching assistant', false),
            ('test_insights', 'Test insights', true),
          ])
            if (profile.features[entry.$1] == true)
              Card(
                child: ListTile(
                  leading: const Icon(Icons.auto_awesome_outlined),
                  title: Text(l(entry.$2)),
                  trailing: const Icon(Icons.chevron_right),
                  onTap: () {
                    bool current() =>
                        repository.matches(generation, school, user) &&
                        repository.profile?.features[entry.$1] == true;
                    open(
                      entry.$1,
                      TeachingScreen(
                        repository: ApiTeachingRepository(
                          repository.client,
                          schoolId: school,
                          readerId: user,
                          contextActive: current,
                        ),
                        insightsOnly: entry.$3,
                        canRead: current,
                        canWrite: current,
                        selectCameraPhoto: selectTeachingCameraPhoto,
                        exportPdf: (bytes, name) async {
                          if (!current()) return;
                          await DeviceFiles.saveDocument(
                            bytes,
                            'private-exports/$user/$school/$name',
                          );
                        },
                      ),
                    );
                  },
                ),
              ),
          if (profile.features['my_grade'] == true)
            Card(
              child: ListTile(
                leading: const Icon(Icons.groups_outlined),
                title: Text(l('My grade')),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => open(
                  'my_grade',
                  MyGradeClientScreen(
                    client: repository.client,
                    schoolId: school,
                    readerId: user,
                    owner: repository,
                    contextActive: () =>
                        repository.matches(generation, school, user) &&
                        repository.profile?.features['my_grade'] == true,
                  ),
                ),
              ),
            ),
          if (profile.features['discipline'] == true)
            Card(
              child: ListTile(
                leading: const Icon(Icons.assignment_ind_outlined),
                title: Text(l('School discipline')),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => open(
                  'discipline',
                  SchoolDisciplineScreen(
                    repository: ApiDisciplineRepository(
                      repository.client,
                      schoolId: school,
                      readerId: user,
                      contextActive: () =>
                          repository.matches(generation, school, user) &&
                          repository.profile?.features['discipline'] == true,
                    ),
                    canRead: () =>
                        repository.matches(generation, school, user) &&
                        repository.profile?.features['discipline'] == true,
                  ),
                ),
              ),
            ),
          if (profile.features['risk_interventions'] == true)
            Card(
              child: ListTile(
                leading: const Icon(Icons.assignment_ind_outlined),
                title: Text(l('Staff follow-up')),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => open(
                  'risk_interventions',
                  RiskInterventionsScreen(
                    repository: ApiRiskRepository(
                      repository.client,
                      schoolId: school,
                      readerId: user,
                      contextActive: () =>
                          repository.matches(generation, school, user) &&
                          repository.profile?.features['risk_interventions'] ==
                              true,
                    ),
                    canRead: () =>
                        repository.matches(generation, school, user) &&
                        repository.profile?.features['risk_interventions'] ==
                            true,
                  ),
                ),
              ),
            ),
          if (profile.features['learner_notes'] == true)
            Card(
              child: ListTile(
                leading: const Icon(Icons.edit_note_outlined),
                title: Text(l('Restricted counselling notes')),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => open(
                  'learner_notes',
                  ProgressNotesScreen(
                    repository: ApiProgressRepository(
                      repository.client,
                      schoolId: school,
                      readerId: user,
                      contextActive: () =>
                          repository.matches(generation, school, user) &&
                          repository.profile?.features['learner_notes'] == true,
                    ),
                    canRead: () =>
                        repository.matches(generation, school, user) &&
                        repository.profile?.features['learner_notes'] == true,
                  ),
                ),
              ),
            ),
          if (profile.features['counselling'] == true)
            Card(
              child: ListTile(
                leading: const Icon(Icons.support_agent),
                title: Text(l('Incidents and counselling')),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => open(
                  'counselling',
                  WellbeingScreen(
                    repository: ApiWellbeingRepository(
                      repository.client,
                      schoolId: school,
                      accountId: user,
                      contextActive: () =>
                          repository.matches(generation, school, user) &&
                          repository.profile?.features['counselling'] == true,
                    ),
                  ),
                ),
              ),
            ),
          if (profile.features['support_plans'] == true)
            Card(
              child: ListTile(
                leading: const Icon(Icons.health_and_safety_outlined),
                title: Text(l('Support plans')),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => open(
                  'support_plans',
                  SupportScreen(
                    repository: ApiSupportRepository(
                      repository.client,
                      schoolId: school,
                      accountId: user,
                      contextActive: () =>
                          repository.matches(generation, school, user) &&
                          repository.profile?.features['support_plans'] == true,
                    ),
                  ),
                ),
              ),
            ),
          if (profile.features['groups'] == true)
            Card(
              child: ListTile(
                leading: const Icon(Icons.groups_outlined),
                title: Text(l('Activity groups')),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => open(
                  'groups',
                  ActivityGroupsScreen(
                    repository: ApiActivityRepository(
                      repository.client,
                      schoolId: school,
                      accountId: user,
                      contextActive: () =>
                          repository.matches(generation, school, user) &&
                          repository.profile?.features['groups'] == true,
                    ),
                  ),
                ),
              ),
            ),
          if (profile.features['messages'] == true)
            Card(
              child: ListTile(
                leading: const Icon(Icons.forum_outlined),
                title: Text(l('School messages')),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => open(
                  'messages',
                  SchoolMessagesScreen(
                    repository: ApiSchoolMessageRepository(
                      repository.client,
                      schoolId: school,
                      readerId: user,
                      contextActive: () =>
                          repository.matches(generation, school, user) &&
                          repository.profile?.features['messages'] == true,
                    ),
                  ),
                ),
              ),
            ),
          if (![
            'exam_coach',
            'family_forms',
            'cards',
            'gallery',
            'consultations',
            'private_notices',
            'discipline',
            'daily_practice',
            'groups',
            'messages',
            'support_plans',
            'learner_notes',
            'risk_interventions',
            'house_points',
            'awards',
            'counselling',
          ].any((key) => profile.features[key] == true))
            Padding(
              padding: const EdgeInsets.all(24),
              child: Text(
                l(
                  'Your school has not enabled an activity area for this account.',
                ),
              ),
            ),
        ],
      ),
    );
  }
}
