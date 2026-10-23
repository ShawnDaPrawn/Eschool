import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_people/src/common/people_widgets.dart';
import 'package:eschool_people/src/profile/profile_cubit.dart';
import 'package:eschool_people/src/profile/profile_file_field.dart';
import 'package:eschool_people/src/profile/profile_repository.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class ProfileScreen extends StatelessWidget {
  const ProfileScreen({
    this.subjects = const [ProfileSubject()],
    this.onSaved,
    this.pickFile = pickProfileFile,
    super.key,
  });
  final List<ProfileSubject> subjects;
  final VoidCallback? onSaved;
  final ProfileFilePicker pickFile;

  static Widget withRepository(
    ProfileRepository repository, {
    List<ProfileSubject> subjects = const [ProfileSubject()],
    VoidCallback? onSaved,
    ProfileFilePicker pickFile = pickProfileFile,
  }) => BlocProvider(
    create: (_) => ProfileCubit(repository)..load(subjects.first),
    child: ProfileScreen(
      subjects: subjects,
      onSaved: onSaved,
      pickFile: pickFile,
    ),
  );

  Future<bool> discard(BuildContext context, ProfileState state) async {
    if (state.draft.isEmpty) return true;
    String label(String key) => SharedUiUtils.getTranslatedLabel(context, key);
    return await showDialog<bool>(
          context: context,
          builder: (context) => AlertDialog(
            title: Text(label(profileDiscardKey)),
            content: Text(label(profileDiscardHintKey)),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(context, false),
                child: Text(label(cancelKey)),
              ),
              FilledButton(
                onPressed: () => Navigator.pop(context, true),
                child: Text(label(profileDiscardKey)),
              ),
            ],
          ),
        ) ??
        false;
  }

  @override
  Widget build(BuildContext context) {
    String label(String key) => SharedUiUtils.getTranslatedLabel(context, key);
    return Scaffold(
      appBar: AppBar(
        title: Text(label(profileDetailsKey)),
        actions: [
          IconButton(
            tooltip: label(profileReloadKey),
            icon: const Icon(Icons.refresh),
            onPressed: () async {
              final cubit = context.read<ProfileCubit>();
              final state = cubit.state;
              if (!state.busy &&
                  await discard(context, state) &&
                  context.mounted) {
                cubit.reload();
              }
            },
          ),
        ],
      ),
      body: BlocConsumer<ProfileCubit, ProfileState>(
        listener: (context, state) {
          if (state.saved) {
            onSaved?.call();
            ScaffoldMessenger.of(
              context,
            ).showSnackBar(SnackBar(content: Text(label(profileSavedKey))));
          } else if (state.error != null &&
              state.status == ProfileStatus.loaded) {
            showErrorSnackBar(context, state.error!);
          }
        },
        builder: (context, state) {
          final cubit = context.read<ProfileCubit>();
          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              if (subjects.length > 1)
                InputDecorator(
                  decoration: InputDecoration(
                    labelText: label(profilePersonKey),
                  ),
                  child: DropdownButtonHideUnderline(
                    child: DropdownButton<ProfileSubject>(
                      value: state.subject,
                      isExpanded: true,
                      items: subjects
                          .map(
                            (subject) => DropdownMenuItem(
                              value: subject,
                              child: Text(
                                subject.label ?? label(profileMyDetailsKey),
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                          )
                          .toList(),
                      onChanged: state.busy
                          ? null
                          : (subject) async {
                              if (subject != null &&
                                  await discard(context, state) &&
                                  context.mounted) {
                                cubit.load(subject);
                              }
                            },
                    ),
                  ),
                ),
              if (state.status == ProfileStatus.loading)
                const Center(child: CircularProgressIndicator()),
              if (state.status == ProfileStatus.failure)
                LoadFailure(error: state.error!, onRetry: cubit.reload),
              if (state.status == ProfileStatus.unavailable)
                const NotAvailableYet(),
              if (state.form != null &&
                  state.form!.availableProfiles.length > 1 &&
                  state.subject.profileType == null)
                InputDecorator(
                  decoration: InputDecoration(labelText: label(profileTypeKey)),
                  child: DropdownButtonHideUnderline(
                    child: DropdownButton<String>(
                      value: state.form!.profileType,
                      isExpanded: true,
                      items: state.form!.availableProfiles
                          .map(
                            (type) => DropdownMenuItem(
                              value: type,
                              child: Text(label('profileType_$type')),
                            ),
                          )
                          .toList(),
                      onChanged: state.busy
                          ? null
                          : (type) async {
                              if (type != null &&
                                  await discard(context, state) &&
                                  context.mounted) {
                                cubit.load(state.subject, profileType: type);
                              }
                            },
                    ),
                  ),
                ),
              if (state.form != null)
                _ProfileFields(
                  key: ValueKey(state.revision),
                  state: state,
                  pickFile: pickFile,
                ),
            ],
          );
        },
      ),
    );
  }
}

class _ProfileFields extends StatefulWidget {
  const _ProfileFields({
    required this.state,
    required this.pickFile,
    super.key,
  });
  final ProfileFilePicker pickFile;
  final ProfileState state;
  @override
  State<_ProfileFields> createState() => _ProfileFieldsState();
}

class _ProfileFieldsState extends State<_ProfileFields> {
  final formKey = GlobalKey<FormState>();
  String label(String key) => SharedUiUtils.getTranslatedLabel(context, key);
  ProfileValue? value(ProfileFormField field) =>
      widget.state.draft[field.key] ?? field.value;
  void change(ProfileFormField field, ProfileValue value) =>
      context.read<ProfileCubit>().change(field, value);
  String? validate(ProfileFormField field, String? text) {
    if (!widget.state.draft.containsKey(field.key)) return null;
    if ((text ?? '').isEmpty) {
      return field.required_ ? label(profileRequiredKey) : null;
    }
    final rules = field.validation;
    if (field.type == 'number') {
      final number = num.tryParse(text!);
      if (!RegExp(r'^-?\d{1,20}(?:\.\d{1,6})?$').hasMatch(text) ||
          number == null ||
          (rules.min != null && number < rules.min!) ||
          (rules.max != null && number > rules.max!)) {
        return label(profileInvalidKey);
      }
    } else if (field.type == 'date') {
      final day = DateTime.tryParse(text!);
      final today = DateTime.now();
      if (day == null ||
          !RegExp(r'^\d{4}-\d{2}-\d{2}$').hasMatch(text) ||
          day.toIso8601String().substring(0, 10) != text ||
          (rules.past == true &&
              day.isAfter(DateTime(today.year, today.month, today.day)))) {
        return label(profileInvalidKey);
      }
    } else if ((rules.min != null && text!.runes.length < rules.min!) ||
        (rules.max != null && text!.runes.length > rules.max!) ||
        (rules.email == true &&
            !RegExp(r'^[^\s@]+@[^\s@]+\.[^\s@]+$').hasMatch(text!))) {
      return label(profileInvalidKey);
    }
    return null;
  }

  Widget field(ProfileFormField field) {
    if (field.type == 'file') {
      return ProfileFileField(
        field: field,
        state: widget.state,
        cubit: context.read<ProfileCubit>(),
        pickFile: widget.pickFile,
      );
    }
    final current = value(field);
    if (!field.editable ||
        ![
          'text',
          'textarea',
          'date',
          'number',
          'dropdown',
          'radio',
          'checkbox',
          'boolean',
        ].contains(field.type)) {
      return ListTile(
        contentPadding: EdgeInsets.zero,
        title: Text(field.label),
        subtitle: Text(
          current?.text ??
              current?.choices?.join(', ') ??
              (current?.flag == null
                  ? label(profileNotProvidedKey)
                  : label(current!.flag! ? yesKey : noKey)),
        ),
      );
    }
    if (field.type == 'boolean') {
      return CheckboxListTile(
        contentPadding: EdgeInsets.zero,
        title: Text(field.label),
        value: current?.flag ?? (field.required_ ? false : null),
        tristate: !field.required_,
        onChanged: widget.state.busy
            ? null
            : (selected) => change(
                field,
                selected == null
                    ? ProfileValue(clear: true)
                    : ProfileValue(flag: selected),
              ),
      );
    }
    if (field.type == 'checkbox') {
      return FormField<List<String>>(
        validator: (_) =>
            widget.state.draft.containsKey(field.key) &&
                field.required_ &&
                (value(field)?.choices ?? []).isEmpty
            ? label(profileRequiredKey)
            : null,
        builder: (state) => Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            SectionHeading(field.label),
            for (final option in field.options)
              CheckboxListTile(
                contentPadding: EdgeInsets.zero,
                title: Text(option),
                value: (current?.choices ?? []).contains(option),
                onChanged: widget.state.busy
                    ? null
                    : (selected) {
                        final choices = [...(current?.choices ?? <String>[])];
                        selected == true
                            ? choices.add(option)
                            : choices.remove(option);
                        change(
                          field,
                          choices.isEmpty && !field.required_
                              ? ProfileValue(clear: true)
                              : ProfileValue(choices: choices),
                        );
                      },
              ),
            if (state.hasError)
              Text(
                state.errorText!,
                style: TextStyle(color: Theme.of(context).colorScheme.error),
              ),
          ],
        ),
      );
    }
    if (field.type == 'dropdown' || field.type == 'radio') {
      return DropdownButtonFormField<String>(
        initialValue: field.options.contains(current?.text)
            ? current?.text
            : null,
        isExpanded: true,
        decoration: InputDecoration(labelText: field.label),
        items: [
          if (!field.required_)
            DropdownMenuItem(
              value: '',
              child: Text(label(profileNotProvidedKey)),
            ),
          ...field.options.map(
            (option) => DropdownMenuItem(value: option, child: Text(option)),
          ),
        ],
        validator: (text) => validate(field, text),
        onChanged: widget.state.busy
            ? null
            : (text) => change(
                field,
                text == null || text.isEmpty
                    ? ProfileValue(clear: true)
                    : ProfileValue(text: text),
              ),
      );
    }
    return TextFormField(
      key: ValueKey('profile-field-${field.key}'),
      initialValue: current?.text,
      enabled: !widget.state.busy,
      decoration: InputDecoration(
        labelText: field.label,
        hintText: field.type == 'date' ? 'YYYY-MM-DD' : null,
      ),
      keyboardType: field.type == 'number'
          ? const TextInputType.numberWithOptions(decimal: true, signed: true)
          : (field.type == 'date'
                ? TextInputType.datetime
                : (field.type == 'textarea'
                      ? TextInputType.multiline
                      : TextInputType.text)),
      minLines: field.type == 'textarea' ? 3 : 1,
      maxLines: field.type == 'textarea' ? 6 : 1,
      autocorrect: !field.sensitive,
      enableSuggestions: !field.sensitive,
      validator: (text) => validate(field, text),
      onChanged: (text) => change(
        field,
        text.isEmpty ? ProfileValue(clear: true) : ProfileValue(text: text),
      ),
    );
  }

  @override
  Widget build(BuildContext context) => Form(
    key: formKey,
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        for (final category in widget.state.form!.categories) ...[
          SectionHeading(category.label),
          for (final definition in category.fields)
            Padding(
              padding: const EdgeInsets.only(bottom: 16),
              child: field(definition),
            ),
        ],
        FilledButton(
          onPressed: widget.state.busy || widget.state.draft.isEmpty
              ? null
              : () {
                  if (formKey.currentState!.validate()) {
                    context.read<ProfileCubit>().save();
                  }
                },
          child: Text(label(profileSaveKey)),
        ),
      ],
    ),
  );
}
