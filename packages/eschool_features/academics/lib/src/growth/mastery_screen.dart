import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../localization/academic_text.dart';
import 'mastery_repository.dart';
import 'mastery_cubit.dart';

class MasteryScreen extends StatefulWidget {
  const MasteryScreen({
    required this.repository,
    this.learner,
    this.learnerName,
    super.key,
  });
  final MasteryRepository repository;
  final int? learner;
  final String? learnerName;
  @override
  State<MasteryScreen> createState() => _MasteryScreenState();
}

class _MasteryScreenState extends State<MasteryScreen>
    with WidgetsBindingObserver {
  late MasteryCubit _cubit;
  int _tab = 0;
  bool _foreground = true;
  String l(String value) => academicText(context, value);
  void _create() {
    _cubit = MasteryCubit(widget.repository, familyLearner: widget.learner)
      ..load();
  }

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _create();
  }

  @override
  void didUpdateWidget(covariant MasteryScreen oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.repository != widget.repository ||
        oldWidget.learner != widget.learner) {
      _cubit.close();
      _create();
    }
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      setState(() => _foreground = true);
      _cubit.load();
    } else if ([
      AppLifecycleState.inactive,
      AppLifecycleState.paused,
      AppLifecycleState.hidden,
    ].contains(state)) {
      setState(() => _foreground = false);
      _cubit.hide();
    }
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _cubit.close();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => ScreenScaffold(
    title: l('Mastery map'),
    action: IconButton(
      tooltip: l('Refresh mastery'),
      onPressed: _cubit.load,
      icon: const Icon(Icons.refresh),
    ),
    body: BlocBuilder<MasteryCubit, MasteryState>(
      bloc: _cubit,
      builder: (context, state) {
        if (!_foreground || state.hidden || !widget.repository.isActive) {
          return const SizedBox.shrink();
        }
        if (state.busy) return const Center(child: CircularProgressIndicator());
        if (state.error != null) {
          return Center(
            child: Padding(
              padding: const EdgeInsets.all(24),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(l(state.error!), textAlign: TextAlign.center),
                  const SizedBox(height: 16),
                  FilledButton(
                    onPressed: _cubit.load,
                    child: AcademicText('Retry'),
                  ),
                ],
              ),
            ),
          );
        }
        final filters = _filters(state);
        final content = state.view == null
            ? Padding(
                padding: const EdgeInsets.all(24),
                child: AcademicText(
                  widget.learner == null
                      ? 'No current learners are allocated to this group.'
                      : 'No subject choices are available. Ask your school to review allocations.',
                ),
              )
            : MasteryOverview(view: state.view!, showTopics: _tab == 1);
        return AdaptiveScaffold(
          maxContentWidth: 1400,
          selectedIndex: _tab,
          onDestinationSelected: (index) => setState(() => _tab = index),
          destinations: [
            AdaptiveDestination(
              icon: const Icon(Icons.explore_outlined),
              selectedIcon: const Icon(Icons.explore),
              label: l('Next topics'),
            ),
            AdaptiveDestination(
              icon: const Icon(Icons.account_tree_outlined),
              selectedIcon: const Icon(Icons.account_tree),
              label: l('Topic evidence'),
            ),
          ],
          body: Column(
            children: [
              Expanded(
                child: LayoutBuilder(
                  builder: (context, constraints) {
                    if (constraints.maxWidth >= 900) {
                      return Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          SizedBox(
                            width: 280,
                            child: SingleChildScrollView(child: filters),
                          ),
                          const VerticalDivider(width: 1),
                          Expanded(
                            child: SingleChildScrollView(child: content),
                          ),
                        ],
                      );
                    }
                    return SingleChildScrollView(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [filters, content],
                      ),
                    );
                  },
                ),
              ),
              if (Breakpoints.of(context) == ScreenSize.phone)
                NavigationBar(
                  selectedIndex: _tab,
                  onDestinationSelected: (index) =>
                      setState(() => _tab = index),
                  destinations: [
                    NavigationDestination(
                      icon: const Icon(Icons.explore_outlined),
                      selectedIcon: const Icon(Icons.explore),
                      label: l('Next topics'),
                    ),
                    NavigationDestination(
                      icon: const Icon(Icons.account_tree_outlined),
                      selectedIcon: const Icon(Icons.account_tree),
                      label: l('Topic evidence'),
                    ),
                  ],
                ),
            ],
          ),
        );
      },
    ),
  );

  Widget _filters(MasteryState state) => Padding(
    padding: const EdgeInsets.all(16),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        if (widget.learnerName != null)
          Padding(
            padding: const EdgeInsets.only(bottom: 16),
            child: Text(
              widget.learnerName!,
              style: Theme.of(context).textTheme.titleMedium,
            ),
          ),
        if (state.configuration != null) ...[
          DropdownButtonFormField<int>(
            isExpanded: true,
            initialValue: state.groupId,
            decoration: InputDecoration(labelText: l('Teaching group')),
            items: state.configuration!.groups
                .map(
                  (g) => DropdownMenuItem(
                    value: g.id,
                    child: Text(
                      '${g.name} · ${g.subjectName}',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                )
                .toList(),
            onChanged: (id) {
              if (id != null) _cubit.group(id);
            },
          ),
          const SizedBox(height: 16),
          if (state.roster != null) ...[
            DropdownButtonFormField<int>(
              isExpanded: true,
              initialValue: state.learnerId,
              decoration: InputDecoration(labelText: l('Learner')),
              items: state.roster!.learners
                  .map(
                    (learner) => DropdownMenuItem(
                      value: learner.learnerId,
                      child: Text(
                        learner.displayName,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  )
                  .toList(),
              onChanged: (id) {
                if (id != null) _cubit.learner(id);
              },
            ),
            if (state.roster!.lastPage > 1)
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  IconButton(
                    tooltip: l('Previous page'),
                    onPressed: state.roster!.currentPage > 1
                        ? () => _cubit.group(
                            state.groupId!,
                            page: state.roster!.currentPage - 1,
                          )
                        : null,
                    icon: const Icon(Icons.chevron_left),
                  ),
                  Text(
                    '${state.roster!.currentPage} / ${state.roster!.lastPage}',
                  ),
                  IconButton(
                    tooltip: l('Next page'),
                    onPressed:
                        state.roster!.currentPage < state.roster!.lastPage
                        ? () => _cubit.group(
                            state.groupId!,
                            page: state.roster!.currentPage + 1,
                          )
                        : null,
                    icon: const Icon(Icons.chevron_right),
                  ),
                ],
              ),
          ],
        ],
        if (state.subjects != null)
          DropdownButtonFormField<int>(
            isExpanded: true,
            initialValue: state.subjectId,
            decoration: InputDecoration(labelText: l('Subject')),
            items: state.subjects!.subjects
                .map(
                  (subject) => DropdownMenuItem(
                    value: subject.id,
                    child: Text(
                      subject.name,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                )
                .toList(),
            onChanged: (id) {
              if (id != null) _cubit.subject(id);
            },
          ),
      ],
    ),
  );
}

class MasteryOverview extends StatelessWidget {
  const MasteryOverview({
    required this.view,
    this.showTopics = false,
    super.key,
  });
  final MasteryView view;
  final bool showTopics;
  @override
  Widget build(BuildContext context) {
    String l(String text) => academicText(context, text);
    final theme = Theme.of(context);
    final topics = {for (final topic in view.topics) topic.id: topic};
    String level(int? value) => value == null
        ? l(view.marksVisible ? 'Not yet assessed' : 'Not shared by school')
        : '${l('Level')} $value / 4';
    return Padding(
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(
            l(showTopics ? 'Topic evidence' : 'Start with these topics'),
            style: theme.textTheme.headlineSmall,
          ),
          const SizedBox(height: 12),
          Text(
            l(
              'Levels use recent evidence. They are learning guidance, not a promotion decision.',
            ),
          ),
          if (!view.marksVisible)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 16),
              child: Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Text(
                    l(
                      'Your school has not enabled mastery from released marks.',
                    ),
                  ),
                ),
              ),
            ),
          if (!showTopics &&
              view.priorities.isEmpty &&
              view.marksVisible &&
              view.topics.isNotEmpty)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 20),
              child: Text(
                l('No current gaps are identified in the available evidence.'),
              ),
            ),
          if (!showTopics)
            ...view.priorities.map((priority) {
              final node = topics[priority.topicId];
              final path = priority.path
                  .map((id) => topics[id]?.name ?? '')
                  .where((name) => name.isNotEmpty)
                  .join(' → ');
              return Card(
                child: Padding(
                  padding: const EdgeInsets.all(20),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(priority.name, style: theme.textTheme.titleLarge),
                      if (node != null) Text(node.gradeName),
                      const SizedBox(height: 8),
                      Text(level(priority.level)),
                      Text(
                        l(
                          priority.reason.value == 'not_yet_assessed'
                              ? 'Try this topic to establish a starting point.'
                              : priority.reason.value == 'prerequisite_gap'
                              ? 'This earlier topic supports your current work.'
                              : 'More practice on this topic may help.',
                        ),
                      ),
                      if (priority.path.length > 1)
                        Padding(
                          padding: const EdgeInsets.only(top: 8),
                          child: Text('${l('Learning path')}: $path'),
                        ),
                    ],
                  ),
                ),
              );
            }),
          if (showTopics)
            ...view.topics.map(
              (topic) => Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(topic.name, style: theme.textTheme.titleMedium),
                      Text('${topic.gradeName} · ${level(topic.level)}'),
                      if (!view.targetTopicIds.contains(topic.id))
                        Text(l('Prerequisite topic')),
                      const SizedBox(height: 8),
                      if (view.marksVisible || topic.level != null)
                        Text('${l('Recent questions')}: ${topic.evidenceCount}')
                      else
                        Text(l('Evidence not shared')),
                      if (topic.evidenceCount > 0) ...[
                        const SizedBox(height: 8),
                        Semantics(
                          label:
                              '${l('Evidence coverage')}: ${topic.confidenceBp ~/ 100}%',
                          child: LinearProgressIndicator(
                            value: topic.confidenceBp / 10000,
                          ),
                        ),
                      ],
                    ],
                  ),
                ),
              ),
            ),
          if (view.topics.isEmpty)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 20),
              child: Text(
                l('No published topics are available for this subject yet.'),
              ),
            ),
        ],
      ),
    );
  }
}
