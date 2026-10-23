import 'dart:typed_data';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_people/src/profile/profile_cubit.dart';
import 'package:eschool_people/src/profile/profile_repository.dart';
import 'package:eschool_people/src/common/people_widgets.dart';
import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';

typedef ProfileFilePicker = Future<ProfileUpload?> Function();

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
    super.key,
  });
  final ProfileFormField field;
  final ProfileState state;
  final ProfileCubit cubit;
  final ProfileFilePicker pickFile;

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
                        Navigator.of(context).push(
                          MaterialPageRoute<void>(
                            builder: (_) => _ProfileFileView(
                              repository:
                                  cubit.repository as ProfileFileRepository,
                              subject: state.subject,
                              file: file,
                            ),
                          ),
                        );
                      },
              ),
            if (field.editable && cubit.repository is ProfileFileRepository)
              OutlinedButton.icon(
                icon: const Icon(Icons.upload_file),
                label: Text(label(profileFileUploadKey)),
                onPressed: state.busy
                    ? null
                    : () async {
                        try {
                          final picked = await pickFile();
                          if (picked == null ||
                              !context.mounted ||
                              cubit.isClosed) {
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
        if (field.editable)
          Text(
            label(profileFileLimitKey),
            style: Theme.of(context).textTheme.bodySmall,
          ),
      ],
    );
  }
}

/// Authenticated bytes live only for this route. No public URL or disk cache.
class _ProfileFileView extends StatefulWidget {
  const _ProfileFileView({
    required this.repository,
    required this.subject,
    required this.file,
  });
  final ProfileFileRepository repository;
  final ProfileSubject subject;
  final ProfileAttachment file;
  @override
  State<_ProfileFileView> createState() => _ProfileFileViewState();
}

class _ProfileFileViewState extends State<_ProfileFileView> {
  Uint8List? bytes;
  MemoryImage? image;
  ApiException? error;
  bool pdfReady = false;
  @override
  void initState() {
    super.initState();
    load();
  }

  Future<void> load() async {
    try {
      final result = await widget.repository.readFile(
        widget.subject,
        widget.file,
      );
      if (!mounted) return;
      setState(() {
        bytes = result;
        if (widget.file.mime != 'application/pdf') image = MemoryImage(result);
      });
    } catch (failure) {
      if (mounted) {
        setState(() {
          error = failure is ApiException
              ? failure
              : ApiException(ApiErrorCodes.defaultErrorMessageKey);
        });
      }
    }
  }

  @override
  void dispose() {
    image?.evict();
    bytes = null;
    image = null;
    super.dispose();
  }

  void retry() {
    image?.evict();
    setState(() {
      error = null;
      bytes = null;
      image = null;
      pdfReady = false;
    });
    load();
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(title: Text(widget.file.name)),
    body: error != null
        ? LoadFailure(error: error!, onRetry: retry)
        : bytes == null
        ? const Center(child: CircularProgressIndicator())
        : widget.file.mime == 'application/pdf'
        ? Stack(
            children: [
              PdfDocumentView(
                bytes: bytes,
                onLoaded: () {
                  if (mounted) {
                    setState(() {
                      pdfReady = true;
                    });
                  }
                },
                onError: () {
                  if (mounted) {
                    setState(() {
                      error = ApiException(
                        ApiErrorCodes.defaultErrorMessageKey,
                      );
                    });
                  }
                },
              ),
              if (!pdfReady) const Center(child: CircularProgressIndicator()),
              if (pdfReady) const SizedBox(key: ValueKey('profile-file-ready')),
            ],
          )
        : InteractiveViewer(
            child: Center(
              child: Image(
                image: image!,
                errorBuilder: (_, _, _) => LoadFailure(
                  error: ApiException(ApiErrorCodes.defaultErrorMessageKey),
                  onRetry: retry,
                ),
              ),
            ),
          ),
  );
}
