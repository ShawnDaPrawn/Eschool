import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_teacher/app/routes.dart';
import 'package:eschool_teacher/cubits/authCubit.dart';
import 'package:eschool_teacher/data/repositories/authRepository.dart';
import 'package:eschool_teacher/data/repositories/policyRepository.dart';
import 'package:eschool_teacher/ui/widgets/errorContainer.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_html/flutter_html.dart';

/// First sign-in: NavigateEd, then the school's privacy notice and terms
/// when the school has written them. Later visits skip this screen.
class PolicyScreen extends StatefulWidget {
  const PolicyScreen({super.key});

  static Route route(RouteSettings routeSettings) {
    return CupertinoPageRoute(builder: (_) => const PolicyScreen());
  }

  @override
  State<PolicyScreen> createState() => _PolicyScreenState();
}

class _PolicyScreenState extends State<PolicyScreen> {
  final PolicyRepository _policies = PolicyRepository();

  Map<String, dynamic>? _status;
  String? _error;
  bool _loading = true;
  bool _saving = false;
  bool _agreed = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final status = await _policies.status();
      if (!mounted) {
        return;
      }
      setState(() {
        _status = status;
        _loading = false;
        _agreed = false;
      });
      if (!PolicyRepository.pending(status)) {
        await _openHome();
      }
    } on ApiException catch (error) {
      if (!mounted) {
        return;
      }
      setState(() {
        _error = error.errorMessage;
        _loading = false;
      });
    } catch (error) {
      if (!mounted) {
        return;
      }
      setState(() {
        _error = error.toString();
        _loading = false;
      });
    }
  }

  Map<String, dynamic>? get _navigateed => _asMap(_status?['navigateed']);

  Map<String, dynamic>? get _school => _asMap(_status?['school']);

  String? get _currentDocument {
    if (_navigateed?['accepted'] != true) {
      return 'navigateed';
    }
    if (_school?['required'] == true && _school?['accepted'] != true) {
      return 'school';
    }
    return null;
  }

  Map<String, dynamic>? _asMap(dynamic value) {
    if (value is Map) {
      return Map<String, dynamic>.from(value);
    }
    return null;
  }

  Future<void> _accept() async {
    final document = _currentDocument;
    final version = document == 'navigateed'
        ? _navigateed?['version']
        : _school?['version'];
    if (document == null || version is! String) {
      return;
    }
    if (!_agreed) {
      UiUtils.showBottomToastOverlay(
        context: context,
        errorMessage: UiUtils.getTranslatedLabel(
          context,
          pleaseAcceptTermsAndConditionKey,
        ),
        backgroundColor: Theme.of(context).colorScheme.error,
      );
      return;
    }

    setState(() => _saving = true);
    try {
      final status = await _policies.accept(
        document: document,
        version: version,
      );
      if (!mounted) {
        return;
      }
      setState(() {
        _status = status;
        _saving = false;
        _agreed = false;
      });
      if (!PolicyRepository.pending(status)) {
        await _openHome();
      }
    } on ApiException catch (error) {
      if (!mounted) {
        return;
      }
      setState(() => _saving = false);
      UiUtils.showBottomToastOverlay(
        context: context,
        errorMessage: error.isApiMessage
            ? error.errorMessage
            : UiUtils.getErrorMessageFromErrorCode(context, error),
        backgroundColor: Theme.of(context).colorScheme.error,
      );
      await _load();
    }
  }

  Future<void> _openHome() async {
    final teacher = await AuthRepository().fetchTeacherProfile();
    if (!mounted) {
      return;
    }
    if (teacher != null) {
      await AuthRepository().setTeacherDetails(teacher);
    }
    if (!mounted) {
      return;
    }
    Navigator.of(context).pushNamedAndRemoveUntil(Routes.home, (route) => false);
  }

  void _leave() {
    context.read<AuthCubit>().signOut();
    Navigator.of(context).pushNamedAndRemoveUntil(Routes.login, (route) => false);
  }

  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: false,
      child: Scaffold(
        body: SafeArea(child: _body()),
      ),
    );
  }

  Widget _body() {
    if (_loading) {
      return Center(
        child: CustomCircularProgressIndicator(
          indicatorColor: Theme.of(context).colorScheme.primary,
        ),
      );
    }
    if (_error != null) {
      return Center(
        child: ErrorContainer(
          errorMessageText: _error,
          onTapRetry: _load,
        ),
      );
    }
    if (_currentDocument == null) {
      return Center(
        child: CustomCircularProgressIndicator(
          indicatorColor: Theme.of(context).colorScheme.primary,
        ),
      );
    }

    final schoolName = (_status?['school_name'] as String?)?.trim() ?? '';
    final title = _currentDocument == 'navigateed'
        ? UiUtils.getTranslatedLabel(context, navigateEdKey)
        : (schoolName.isEmpty
              ? UiUtils.getTranslatedLabel(context, yourSchoolKey)
              : schoolName);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(20, 16, 20, 0),
          child: Text(
            title,
            style: Theme.of(context).textTheme.headlineSmall,
          ),
        ),
        Expanded(
          child: SingleChildScrollView(
            padding: const EdgeInsets.fromLTRB(20, 12, 20, 12),
            child: _currentDocument == 'navigateed'
                ? Text(
                    (_navigateed?['body'] as String?) ?? '',
                    style: Theme.of(context).textTheme.bodyLarge,
                  )
                : _schoolDocuments(),
          ),
        ),
        CheckboxListTile(
          value: _agreed,
          onChanged: _saving
              ? null
              : (value) => setState(() => _agreed = value ?? false),
          controlAffinity: ListTileControlAffinity.leading,
          title: Text(
            UiUtils.getTranslatedLabel(context, iHaveReadAndAcceptKey),
          ),
        ),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 20),
          child: CustomRoundedButton(
            onTap: _saving ? () {} : _accept,
            widthPercentage: 1,
            backgroundColor: Theme.of(context).colorScheme.primary,
            buttonTitle: UiUtils.getTranslatedLabel(
              context,
              acceptAndContinueKey,
            ),
            titleColor: Theme.of(context).colorScheme.onPrimary,
            showBorder: false,
            child: _saving
                ? const CustomCircularProgressIndicator(
                    strokeWidth: 2,
                    widthAndHeight: 20,
                  )
                : null,
          ),
        ),
        TextButton(
          onPressed: _saving ? null : _leave,
          child: Text(UiUtils.getTranslatedLabel(context, notNowKey)),
        ),
      ],
    );
  }

  Widget _schoolDocuments() {
    final privacy = (_school?['privacy_policy'] as String?) ?? '';
    final terms = (_school?['terms'] as String?) ?? '';

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          UiUtils.getTranslatedLabel(context, schoolPoliciesIntroKey),
          style: Theme.of(context).textTheme.bodyLarge,
        ),
        if (privacy.isNotEmpty) ...[
          const SizedBox(height: 16),
          Text(
            UiUtils.getTranslatedLabel(context, privacyPolicyKey),
            style: Theme.of(context).textTheme.titleMedium,
          ),
          Html(data: privacy),
        ],
        if (terms.isNotEmpty) ...[
          const SizedBox(height: 16),
          Text(
            UiUtils.getTranslatedLabel(context, termsAndConditionKey),
            style: Theme.of(context).textTheme.titleMedium,
          ),
          Html(data: terms),
        ],
      ],
    );
  }
}
