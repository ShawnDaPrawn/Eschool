import 'dart:typed_data';
import 'dart:ui' as ui;

import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_people/src/profile/profile_cubit.dart';
import 'package:eschool_people/src/profile/profile_repository.dart';
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

class _ProfileFileViewState extends State<_ProfileFileView>
    with WidgetsBindingObserver {
  Uint8List? bytes;
  ui.Image? image;
  ApiException? error;
  bool pdfReady = false, suspended = false;
  int generation = 0;
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    load();
  }

  void clearBytes() {
    image?.dispose();
    image = null;
    bytes?.fillRange(0, bytes!.length, 0);
    bytes = null;
    pdfReady = false;
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state != AppLifecycleState.resumed) {
      generation++;
      suspended = true;
      clearBytes();
      if (mounted) setState(() => error = null);
    } else {
      suspended = false;
      if (mounted) {
        setState(() {});
        load();
      }
    }
  }

  Future<void> load() async {
    final epoch = ++generation;
    Uint8List? result;
    ui.Codec? codec;
    try {
      result = await widget.repository.readFile(widget.subject, widget.file);
      if (!mounted || suspended || epoch != generation) {
        result.fillRange(0, result.length, 0);
        return;
      }
      if (widget.file.mime != 'application/pdf') {
        codec = await ui.instantiateImageCodec(
          result,
          targetWidth: 1024,
          allowUpscaling: false,
        );
        final frame = await codec.getNextFrame();
        if (!mounted || suspended || epoch != generation) {
          frame.image.dispose();
          result.fillRange(0, result.length, 0);
          return;
        }
        image = frame.image;
      }
      setState(() => bytes = result);
    } catch (failure) {
      result?.fillRange(0, result.length, 0);
      if (mounted && !suspended && epoch == generation) {
        setState(() {
          error = failure is ApiException
              ? failure
              : ApiException(ApiErrorCodes.defaultErrorMessageKey);
        });
      }
    } finally {
      codec?.dispose();
    }
  }

  @override
  void dispose() {
    generation++;
    WidgetsBinding.instance.removeObserver(this);
    clearBytes();
    super.dispose();
  }

  void retry() {
    image?.dispose();
    bytes?.fillRange(0, bytes!.length, 0);
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
    body: suspended
        ? const SizedBox.shrink()
        : error != null
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
              child: Semantics(
                image: true,
                label: widget.file.name,
                child: RawImage(image: image, fit: BoxFit.contain),
              ),
            ),
          ),
  );
}
