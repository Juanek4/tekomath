"""Use the bundled DOCX renderer with native Word conversion on this Windows host."""
from pathlib import Path
import importlib.util, os, subprocess, sys, tempfile
ROOT=Path(__file__).resolve().parents[2]
QA=Path(__file__).resolve().parent
scratch=QA/'tmp';scratch.mkdir(exist_ok=True)
assert scratch.resolve().is_relative_to(ROOT.resolve())
os.environ['TMP']=str(scratch);os.environ['TEMP']=str(scratch);tempfile.tempdir=str(scratch)
os.environ['PATH']=r'C:/Users/bogad/.cache/codex-runtimes/codex-primary-runtime/dependencies/native/poppler/Library/bin'+os.pathsep+os.environ.get('PATH','')
SKILL=Path(r'C:/Users/bogad/.codex/plugins/cache/openai-primary-runtime/documents/26.1026.10807/skills/documents')
spec=importlib.util.spec_from_file_location('bundled_docx_renderer',SKILL/'render_docx.py');renderer=importlib.util.module_from_spec(spec);spec.loader.exec_module(renderer)
def word_pdf(doc_path,profile,directory,stem,verbose=False):
    target=Path(directory)/(stem+'.pdf')
    script='''$word=$null;$doc=$null;try{$word=New-Object -ComObject Word.Application;$word.Visible=$false;$word.DisplayAlerts=0;$word.AutomationSecurity=3;$word.Options.SaveNormalPrompt=$false;$doc=$word.Documents.Open($env:TEKO_RENDER_INPUT,$false,$true,$false);$doc.Fields.Update()|Out-Null;foreach($section in $doc.Sections){foreach($footer in $section.Footers){$footer.Range.Fields.Update()|Out-Null}};$doc.Repaginate();$doc.ExportAsFixedFormat($env:TEKO_RENDER_PDF,17);Write-Output ('Pages: '+$doc.ComputeStatistics(2))}finally{if($doc){$doc.Close(0);[System.Runtime.InteropServices.Marshal]::ReleaseComObject($doc)|Out-Null};if($word){$word.Quit();[System.Runtime.InteropServices.Marshal]::ReleaseComObject($word)|Out-Null}}'''
    env=dict(os.environ,TEKO_RENDER_INPUT=str(Path(doc_path).resolve()),TEKO_RENDER_PDF=str(target.resolve()))
    result=subprocess.run(['powershell.exe','-NoProfile','-NonInteractive','-Command',script],env=env,capture_output=True,text=True,timeout=180)
    if result.returncode or not target.exists():raise RuntimeError('Word PDF export failed: '+result.stderr)
    return str(target),result.stdout
renderer.convert_to_pdf=word_pdf
renderer.main()
