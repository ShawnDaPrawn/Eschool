import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_core/eschool_core.dart';
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
          return Stack(
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
    void open(String module, Widget child) {
      Navigator.of(context).push(
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
            'groups',
            'messages',
            'support_plans',
            'learner_notes',
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
