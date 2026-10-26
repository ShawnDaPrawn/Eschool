import 'dart:typed_data';

import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_people/src/profile/profile_cubit.dart';
import 'package:eschool_people/src/profile/profile_repository.dart';
import 'package:eschool_people/src/profile/private_document_view.dart';
import 'package:eschool_people/src/profile/profile_context_boundary.dart';
import 'package:eschool_people/src/common/people_widgets.dart';
import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';

typedef ProfileFilePicker = Future<ProfileUpload?> Function();

/// An enclosing private route owns selection, errors and fresh authorization.
/// It may immediately dispose this editor; no medical form is retained awaiting it.
typedef ProfileFileSelector =
    void Function(ProfileSubject subject, String field);

Future<ProfileUpload?> pickProfileFile() async {
  final file = await FilePicker.pickFile(
    type: FileType.custom,
    allowedExtensions: ['pdf', 'png', 'jpg', 'jpeg'],
  );
  if (file == null) return null;
  if ((await file.length() ?? 0) > 5 * 1024 * 1024) {
    throw ApiException(profileFileLimitKey);
  }
  final bytes = BytesBuilder(copy: false);
  await for (final chunk in file.readAsByteStream()) {
    if (bytes.length + chunk.length > 5 * 1024 * 1024) {
      throw ApiException(profileFileLimitKey);
    }
    bytes.add(chunk);
  }
  return ProfileUpload(file.name, bytes.takeBytes());
}

class ProfileFileField extends StatelessWidget {
  const ProfileFileField({
    required this.field,
    required this.state,
    required this.cubit,
    this.pickFile = pickProfileFile,
    this.selectFile,
    super.key,
  });
  final ProfileFormField field;
  final ProfileState state;
  final ProfileCubit cubit;
  final ProfileFilePicker pickFile;
  final ProfileFileSelector? selectFile;

  @override
  Widget build(BuildContext context) {
    String label(String key) => SharedUiUtils.getTranslatedLabel(context, key);
    final file = field.value?.file;
    final clearing = state.draft[field.key]?.clear == true;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SectionHeading(field.label),
        Text(
          file?.name ?? label(profileNotProvidedKey),
          overflow: TextOverflow.ellipsis,
        ),
        Wrap(
          spacing: 8,
          children: [
            if (file != null && !clearing)
              TextButton.icon(
                icon: const Icon(Icons.visibility_outlined),
                label: Text(label(profileFileViewKey)),
                onPressed:
                    state.busy || cubit.repository is! ProfileFileRepository
                    ? null
                    : () {
                        final lease = ProfileReadScope.maybeOf(context);
                        Navigator.of(context).push(
                          MaterialPageRoute<void>(
                            builder: (_) => _ProfileFileView(
                              repository:
                                  cubit.repository as ProfileFileRepository,
                              subject: state.subject,
                              file: file,
                              lease: lease,
                            ),
                          ),
                        );
                      },
              ),
            if (field.editable && cubit.repository is ProfileFileRepository)
              OutlinedButton.icon(
                icon: const Icon(Icons.upload_file),
                label: Text(label(profileFileUploadKey)),
                onPressed:
                    state.busy || (selectFile != null && state.draft.isNotEmpty)
                    ? null
                    : () async {
                        if (selectFile != null) {
                          selectFile!(state.subject, field.key);
                          return;
                        }
                        try {
                          final picked = await pickFile();
                          if (picked == null) return;
                          if (!context.mounted || cubit.isClosed) {
                            picked.bytes.fillRange(0, picked.bytes.length, 0);
                            return;
                          }
                          if (picked.bytes.length > 5 * 1024 * 1024) {
                            throw ApiException(profileFileLimitKey);
                          }
                          await cubit.upload(
                            field,
                            picked,
                            subject: state.subject,
                            revision: state.revision,
                          );
                        } catch (error) {
                          if (context.mounted) {
                            showErrorSnackBar(
                              context,
                              error is ApiException
                                  ? error
                                  : ApiException(
                                      ApiErrorCodes.defaultErrorMessageKey,
                                    ),
                            );
                          }
                        }
                      },
              ),
          ],
        ),
        if (field.editable && file != null && !field.required_)
          CheckboxListTile(
            contentPadding: EdgeInsets.zero,
            title: Text(label(profileFileClearKey)),
            value: clearing,
            onChanged: state.busy
                ? null
                : (selected) => selected == true
                      ? cubit.change(field, ProfileValue(clear: true))
                      : cubit.reset(field),
          ),
        if (field.editable && selectFile != null && state.draft.isNotEmpty)
          Text(label('Save your changes before adding a document.')),
        if (field.editable)
          Text(
            label(profileFileLimitKey),
            style: Theme.of(context).textTheme.bodySmall,
          ),
      ],
    );
  }
}

class _ProfileFileView extends StatelessWidget {
  const _ProfileFileView({
    required this.repository,
    required this.subject,
    required this.file,
    this.lease,
  });
  final ProfileReadLease? lease;
  final ProfileFileRepository repository;
  final ProfileSubject subject;
  final ProfileAttachment file;
  @override
  Widget build(BuildContext context) => PrivateDocumentView(
    identity: (
      repository,
      subject.schoolId,
      subject.personId,
      subject.profileType,
      file.id,
    ),
    name: file.name,
    mime: file.mime,
    loadBytes: () => repository.readFile(subject, file),
    isActive: () => lease?.isActive ?? true,
    authorizationChanges: lease,
  );
}
