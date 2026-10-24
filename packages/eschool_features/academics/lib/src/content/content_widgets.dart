import 'dart:typed_data';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_markdown_plus/flutter_markdown_plus.dart';
import 'package:url_launcher/url_launcher.dart';
import '../marks/academic_context_boundary.dart';
import 'content_repository.dart';

String contentLabel(BuildContext context, String text) =>
    (AppLocalization.of(context)?.getTranslatedValues(text) ?? text).trim();
typedef ContentFilePicker = Future<ContentUpload?> Function();
Future<ContentUpload?> pickLearningContent() async {
  final file = await FilePicker.pickFile(
    type: FileType.custom,
    allowedExtensions: [
      'pdf',
      'docx',
      'pptx',
      'xlsx',
      'txt',
      'md',
      'wav',
      'mp3',
      'mp4',
      'm4a',
      'webm',
      'ogg',
    ],
  );
  if (file == null) return null;
  const maximum = 25 * 1024 * 1024;
  if ((await file.length() ?? maximum + 1) > maximum) {
    throw ApiException(
      'Choose a source file up to 25 MiB.',
      isApiMessage: true,
      statusCode: 422,
    );
  }
  final bytes = BytesBuilder(copy: false);
  await for (final chunk in file.readAsByteStream()) {
    if (bytes.length + chunk.length > maximum) {
      throw ApiException(
        'Choose a source file up to 25 MiB.',
        isApiMessage: true,
        statusCode: 422,
      );
    }
    bytes.add(chunk);
  }
  if (bytes.length == 0) {
    throw ApiException(
      'Choose a non-empty source file.',
      isApiMessage: true,
      statusCode: 422,
    );
  }
  return ContentUpload(file.name, bytes.takeBytes());
}

class ContentMarkdownView extends StatelessWidget {
  const ContentMarkdownView({required this.markdown, super.key});
  final String markdown;
  @override
  Widget build(BuildContext context) => MarkdownBody(
    data: markdown,
    selectable: true,
    imageBuilder: (_, _, _) => const SizedBox.shrink(),
    styleSheet: MarkdownStyleSheet.fromTheme(
      Theme.of(context),
    ).copyWith(tableColumnWidth: const FlexColumnWidth()),
    onTapLink: (_, href, _) async {
      final uri = Uri.tryParse(href ?? '');
      if (uri == null ||
          uri.scheme != 'https' ||
          uri.host.isEmpty ||
          uri.userInfo.isNotEmpty) {
        return;
      }
      if (!await launchUrl(uri, mode: LaunchMode.externalApplication) &&
          context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(contentLabel(context, 'Could not open this link.')),
          ),
        );
      }
    },
  );
}

class ContentMessage extends StatelessWidget {
  const ContentMessage(this.message, {this.retry, super.key});
  final String message;
  final VoidCallback? retry;
  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.all(16),
    child: Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Text(contentLabel(context, message)),
        if (retry != null)
          TextButton(
            onPressed: retry,
            child: Text(contentLabel(context, 'Try again')),
          ),
      ],
    ),
  );
}

/// Both the session identity and the selected school/child own private route state.
class ContentContextBoundary extends StatefulWidget {
  const ContentContextBoundary({
    required this.schoolId,
    required this.readerId,
    required this.child,
    this.learnerId,
    super.key,
  });
  final int schoolId, readerId;
  final int? learnerId;
  final Widget child;
  @override
  State<ContentContextBoundary> createState() => _ContentContextBoundaryState();
}

class _ContentContextBoundaryState extends State<ContentContextBoundary> {
  bool _valid = true;
  void _check(MeContextState state) {
    if (state.context?.user?.id == widget.readerId) return;
    if (!_valid) return;
    setState(() => _valid = false);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted && (ModalRoute.of(context)?.isActive ?? false)) {
        Navigator.of(context).popUntil((route) => route.isFirst);
      }
    });
  }

  @override
  void initState() {
    super.initState();
    _valid =
        context.read<MeContextCubit>().context?.user?.id == widget.readerId;
  }

  @override
  Widget build(BuildContext context) =>
      BlocListener<MeContextCubit, MeContextState>(
        listener: (_, state) => _check(state),
        child: AcademicContextBoundary(
          schoolId: widget.schoolId,
          childId: widget.learnerId,
          child: _valid ? widget.child : const SizedBox.shrink(),
        ),
      );
}

class ContentLinkTarget {
  const ContentLinkTarget({required this.learner, required this.version});
  final int learner, version;
}

ContentLinkTarget? contentLinkArguments(
  Map<String, String> values,
  Map<String, String> query,
) {
  if (!query.containsKey('learner') && !query.containsKey('version')) {
    return null;
  }
  int positive(String key) {
    final value = query[key] ?? '';
    if (!RegExp(r'^[1-9][0-9]{0,14}$').hasMatch(value)) {
      throw FormatException('Invalid learning content link: $key');
    }
    return int.parse(value);
  }

  return ContentLinkTarget(
    learner: positive('learner'),
    version: positive('version'),
  );
}
